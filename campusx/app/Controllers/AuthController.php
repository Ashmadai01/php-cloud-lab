<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Core\Mailer;
use SdutSwap\Core\Validator;
use SdutSwap\Repositories\TokenRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Services\SessionService;

final class AuthController extends Controller
{
    public function showRegister(): void
    {
        if (Auth::check()) redirect('/');
        $this->view('auth/register', ['title' => 'Create your account']);
    }

    public function register(): void
    {
        $this->requireCsrf();
        $this->rateLimit('register', 5, 3600, 3600);
        $name = $this->input('name');
        $email = strtolower($this->input('email'));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $terms = $_POST['terms'] ?? null;

        $v = new Validator();
        $v->required('name', $name, 2, 100)->email('email', $email)->password('password', $password);
        if ($password !== $confirm) $v->add('password_confirm', 'Passwords do not match.');
        if ($terms !== '1') $v->add('terms', 'You must accept the Terms of Service.');
        $domains = array_filter(array_map('trim', explode(',', Config::get('ALLOWED_EMAIL_DOMAINS', ''))));
        if ($domains !== []) {
            $domain = substr(strrchr($email, '@') ?: '', 1);
            if (!in_array(strtolower($domain), array_map('strtolower', $domains), true)) {
                $v->add('email', 'Please use a university email address.');
            }
        }
        if ($v->fails()) {
            setOld(['name' => $name, 'email' => $email]);
            $this->view('auth/register', ['title' => 'Create your account', 'errors' => $v->errors()], 422);
        }
        $users = new UserRepository();
        if ($users->findByEmail($email)) {
            // Generic response to prevent enumeration: pretend success.
            flash('success', 'If that email is available, we sent a verification link. Please check your inbox.');
            redirect('/login');
        }
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
        if ($hash === false) $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $id = $users->create($name, $email, (string) $hash);
        $token = bin2hex(random_bytes(32));
        (new TokenRepository())->createEmailVerification($id, $token, 24);
        Mailer::verificationEmail($email, $name, $token);
        flash('success', 'Account created! Please verify your email to continue.');
        redirect('/login');
    }

    public function showLogin(): void
    {
        if (Auth::check()) redirect('/');
        $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        $this->requireCsrf();
        $this->rateLimit('login', 10, 900, 900);
        $email = strtolower($this->input('email'));
        $password = (string) ($_POST['password'] ?? '');
        $users = new UserRepository();
        $user = $users->findByEmail($email);
        $generic = 'Invalid email or password.';
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            \SdutSwap\Core\Audit::security('login_failed', 'low', 'Failed login attempt');
            setOld(['email' => $email]);
            $this->view('auth/login', ['title' => 'Sign in', 'errors' => ['email' => [$generic]]], 401);
        }
        if (!empty($user['suspended_at'])) {
            $this->view('auth/login', ['title' => 'Sign in', 'errors' => ['email' => ['Your account has been suspended. Please contact support.']]], 403);
        }
        if (Config::bool('REQUIRE_EMAIL_VERIFICATION') && empty($user['email_verified_at'])) {
            $this->view('auth/login', ['title' => 'Sign in', 'errors' => ['email' => ['Please verify your email first. Check your inbox for the verification link.']]], 403);
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID)) {
            $new = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
            if ($new !== false) $users->updatePassword((int) $user['id'], (string) $new);
        }
        Auth::login($user);
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect('/');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot password']);
    }

    public function forgot(): void
    {
        $this->requireCsrf();
        $this->rateLimit('forgot', 5, 3600, 3600);
        $email = strtolower($this->input('email'));
        $users = new UserRepository();
        $user = $users->findByEmail($email);
        if ($user && empty($user['suspended_at'])) {
            $token = bin2hex(random_bytes(32));
            (new TokenRepository())->createPasswordReset((int) $user['id'], $token, 60);
            Mailer::resetEmail($email, (string) $user['name'], $token);
        }
        // Generic response always.
        $this->view('auth/forgot_sent', ['title' => 'Check your email']);
    }

    public function showReset(): void
    {
        $token = $this->input('token');
        $this->view('auth/reset', ['title' => 'Reset password', 'token' => $token]);
    }

    public function reset(): void
    {
        $this->requireCsrf();
        $this->rateLimit('reset', 10, 3600, 3600);
        $token = $this->input('token');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $v = new Validator();
        $v->password('password', $password);
        if ($password !== $confirm) $v->add('password_confirm', 'Passwords do not match.');
        if ($v->fails()) {
            $this->view('auth/reset', ['title' => 'Reset password', 'token' => $token, 'errors' => $v->errors()], 422);
        }
        $userId = (new TokenRepository())->consumePasswordReset($token);
        if ($userId === null) {
            $this->view('auth/reset', ['title' => 'Reset password', 'token' => '', 'errors' => ['token' => ['This reset link is invalid or has expired. Please request a new one.']]], 422);
        }
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
        if ($hash === false) $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        (new UserRepository())->updatePassword($userId, (string) $hash);
        // A reset means the old password may be compromised: sign the account out everywhere.
        SessionService::revokeAllForUser($userId);
        \SdutSwap\Core\Audit::security('password_reset', 'medium', 'Password reset completed', $userId);
        flash('success', 'Your password has been reset. Please sign in.');
        redirect('/login');
    }

    public function verifyEmail(): void
    {
        $token = $this->input('token');
        $userId = (new TokenRepository())->consumeEmailVerification($token);
        if ($userId === null) {
            flash('error', 'This verification link is invalid or has expired. Please sign in and request a new one.');
            redirect('/login');
        }
        (new UserRepository())->setEmailVerified($userId);
        flash('success', 'Email verified! You can now sign in.');
        redirect('/login');
    }

    public function resendVerification(): void
    {
        $this->requireCsrf();
        $this->rateLimit('resend_verify', 3, 3600, 3600);
        $email = strtolower($this->input('email'));
        $user = (new UserRepository())->findByEmail($email);
        if ($user && empty($user['email_verified_at'])) {
            $token = bin2hex(random_bytes(32));
            (new TokenRepository())->createEmailVerification((int) $user['id'], $token, 24);
            Mailer::verificationEmail($email, (string) $user['name'], $token);
        }
        flash('success', 'If that account needs verification, a new link has been sent.');
        redirect('/login');
    }
}
