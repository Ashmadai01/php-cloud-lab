<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Core\Upload;
use SdutSwap\Core\Validator;
use SdutSwap\Repositories\ListingRepository;

final class ListingController extends Controller
{
    private const CONDITIONS = ['new', 'like_new', 'good', 'fair', 'poor'];
    private const STATUSES = ['draft', 'active', 'sold', 'hidden', 'removed'];
    // SDUT Swap prices everything in Chinese yuan (¥ / CNY); the client cannot choose another currency.
    private const CURRENCY = 'CNY';

    public function browse(): void
    {
        $repo = new ListingRepository();
        $filters = [
            'q' => $this->input('q'),
            'category_id' => $this->input('category_id'),
            'condition' => $this->input('condition'),
            'min_price' => $this->input('min_price'),
            'max_price' => $this->input('max_price'),
            'sort' => $this->input('sort', 'newest'),
        ];
        $page = max(1, (int) ($this->input('page', '1')));
        $perPage = 12;
        $total = $repo->countSearch($filters);
        $listings = $repo->search($filters, $page, $perPage);
        $this->view('listings/browse', [
            'title' => 'Browse Marketplace',
            'listings' => $listings,
            'categories' => $repo->categories(),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => (int) ceil($total / $perPage),
            'total' => $total,
        ]);
    }

    public function show(int $id): void
    {
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing) abort(404, 'Listing not found');
        $me = Auth::user();
        $isOwner = $me && (int) $me['id'] === (int) $listing['seller_id'];
        if ($listing['status'] !== 'active' && !$isOwner && !Auth::isAdmin()) {
            abort(404, 'Listing not found');
        }
        if ($listing['status'] === 'active') $repo->incrementViews($id);
        $this->view('listings/show', [
            'title' => (string) $listing['title'],
            'listing' => $listing,
            'images' => $repo->images($id),
            'isFavorite' => $me ? $repo->isFavorite((int) $me['id'], $id) : false,
            'isOwner' => (bool) $isOwner,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $this->assertCanList();
        $repo = new ListingRepository();
        $this->view('listings/create', ['title' => 'Sell an item', 'categories' => $repo->categories()]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->assertCanList();
        $this->rateLimit('listing_create', 20, 3600, 3600);
        $data = $this->validateListingInput();
        if ($data === null) {
            $repo = new ListingRepository();
            $this->view('listings/create', ['title' => 'Sell an item', 'categories' => $repo->categories(), 'errors' => $this->lastErrors], 422);
        }
        $repo = new ListingRepository();
        $id = $repo->create($data);
        $this->handleImages($repo, $id);
        flash('success', 'Listing created!');
        redirect('/listings/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireLogin();
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing) abort(404, 'Listing not found');
        if ((int) $listing['seller_id'] !== Auth::id() && !Auth::isAdmin()) abort(403, 'Forbidden');
        $this->view('listings/edit', ['title' => 'Edit listing', 'listing' => $listing, 'images' => $repo->images($id), 'categories' => $repo->categories()]);
    }

    public function update(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing) abort(404, 'Listing not found');
        if ((int) $listing['seller_id'] !== Auth::id() && !Auth::isAdmin()) abort(403, 'Forbidden');
        $data = $this->validateListingInput();
        if ($data === null) {
            $this->view('listings/edit', ['title' => 'Edit listing', 'listing' => $listing, 'images' => $repo->images($id), 'categories' => $repo->categories(), 'errors' => $this->lastErrors], 422);
        }

        // Listing status must be changed through dedicated workflows.
        $data['status'] = $listing['status'];

        $repo->update($id, $data);
        $this->handleImages($repo, $id);
        flash('success', 'Listing updated!');
        redirect('/listings/' . $id);
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing) abort(404, 'Listing not found');
        if ((int) $listing['seller_id'] !== Auth::id() && !Auth::isAdmin()) abort(403, 'Forbidden');
        $repo->setStatus($id, 'removed');
        if (Auth::isAdmin()) \SdutSwap\Core\Audit::log('listing_removed', 'listing', $id);
        flash('success', 'Listing removed.');
        redirect('/my-listings');
    }

    public function myListings(): void
    {
        Auth::requireLogin();
        $repo = new ListingRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $this->view('listings/my', ['title' => 'My Listings', 'listings' => $repo->bySeller(Auth::id(), $page, 20), 'stats' => $repo->dashboardStatsForSeller(Auth::id()), 'page' => $page]);
    }

    public function toggleFavorite(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing || $listing['status'] !== 'active') abort(404, 'Listing not found');
        $fav = $repo->toggleFavorite(Auth::id(), $id);
        $message = $fav ? 'Added to favorites.' : 'Removed from favorites.';

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            $this->json(['success' => true, 'isFavorite' => $fav, 'message' => $message]);
        }

        flash('success', $message);
        redirect('/listings/' . $id);
    }

    public function favorites(): void
    {
        Auth::requireLogin();
        $repo = new ListingRepository();
        $this->view('listings/favorites', ['title' => 'My Favorites', 'listings' => $repo->favoritesOf(Auth::id())]);
    }

    public function deleteImage(int $listingId, int $imageId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $repo = new ListingRepository();
        $listing = $repo->findById($listingId);
        if (!$listing) abort(404, 'Listing not found');
        if ((int) $listing['seller_id'] !== Auth::id() && !Auth::isAdmin()) abort(403, 'Forbidden');
        $path = $repo->deleteImage($imageId);
        if ($path) Upload::deletePublic($path);
        flash('success', 'Image deleted.');
        redirect('/listings/' . $listingId . '/edit');
    }

    private array $lastErrors = [];

    private function assertCanList(): void
    {
        if (Config::bool('REQUIRE_VERIFIED_TO_LIST') && !isVerifiedUser() && !Auth::isAdmin()) {
            flash('error', 'Please verify your student identity before publishing listings.');
            redirect('/verify');
        }
    }

    private function validateListingInput(): ?array
    {
        $v = new Validator();
        $title = $this->input('title');
        $desc = $this->input('description');
        $price = $this->input('price');
        $currency = self::CURRENCY;
        $categoryId = $this->input('category_id');
        $condition = $this->input('condition', 'good');
        $campus = $this->input('campus');
        $status = $this->input('status', 'active');
        $v->required('title', $title, 3, 150)->required('description', $desc, 10, 5000);
        $v->numericRange('price', $price, 0, 1000000);
        $v->inList('condition', $condition, self::CONDITIONS);
        $v->inList('status', $status, self::STATUSES);
        if ($categoryId !== '' && !ctype_digit($categoryId)) $v->add('category_id', 'Invalid category.');
        if ($campus !== '' && mb_strlen($campus) > 150) $v->add('campus', 'Campus must be at most 150 characters.');
        if ($v->fails()) { $this->lastErrors = $v->errors(); return null; }
        return [
            'seller_id' => Auth::id(),
            'title' => $title, 'description' => $desc,
            'price' => (float) $price, 'currency' => $currency,
            'category_id' => $categoryId === '' ? null : (int) $categoryId,
            'item_condition' => $condition, 'campus' => $campus ?: null,
            'status' => $status,
        ];
    }

    private function handleImages(ListingRepository $repo, int $listingId): void
    {
        if (empty($_FILES['images']) || !is_array($_FILES['images']['name'] ?? null)) return;
        $max = Config::int('UPLOAD_MAX_SIZE', 5242880);
        $names = $_FILES['images']['name'];
        $count = 0;
        foreach ($names as $i => $name) {
            if ($count >= 6) break;
            $file = [
                'name' => $names[$i], 'type' => $_FILES['images']['type'][$i] ?? '',
                'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
                'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['images']['size'][$i] ?? 0,
            ];
            if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
            // Run server-side SafeSearch before saving or publishing the image.
            $moderation = ImageModeration::inspect((string) ($file['tmp_name'] ?? ''));
            if (!$moderation['ok']) {
                flash('error', 'One image was blocked: ' . (string) ($moderation['error'] ?? 'Safety check failed.'));
                continue;
            }

            $res = Upload::storeImage($file, 'listings/' . $listingId, $max);
            if ($res['ok']) {
                $repo->addImage($listingId, (string) $res['path'], $repo->nextImageOrder($listingId));
                $count++;
            } else {
                flash('error', 'One image was rejected: ' . $res['error']);
            }
        }
    }
}
