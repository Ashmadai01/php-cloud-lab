<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Repositories\ListingRepository;

final class HomeController extends Controller
{
    public function index(): void
    {
        $repo = new ListingRepository();
        $this->view('home/index', [
            'title' => 'SDUT Swap — Your Campus. Your Marketplace.',
            'featured' => $repo->featured(8),
            'latest' => $repo->latest(8),
            'categories' => $repo->categories(),
        ]);
    }

    public function page(string $slug): void
    {
        $pages = ['about', 'guidelines', 'privacy', 'terms', 'safety'];
        if (!in_array($slug, $pages, true)) abort(404, 'Page not found');
        $titles = [
            'about' => 'About SDUT Swap',
            'guidelines' => 'Community Guidelines',
            'privacy' => 'Privacy Policy',
            'terms' => 'Terms of Service',
            'safety' => 'Safety Tips',
        ];
        $this->view('pages/' . $slug, ['title' => $titles[$slug]]);
    }
}
