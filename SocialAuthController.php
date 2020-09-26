<?php

namespace App\Http\Controllers\Auth;
use App\User;
use Auth;
use Socialite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class SocialAuthController extends Controller
{
	use AuthenticatesUsers;
	protected $redirectTo = '/home';
	public function redirectToProvider()
	{
		return Socialite::driver('twitter')->redirect();
	}
	public function handleProviderCallback()
	{
		try {
			$user = Socialite::driver('twitter')->user();
		} catch (Exception $e) {
			return redirect('auth/twitter');
		}

		$authUser = $this->findOrCreateUser($user);

		Auth::login($authUser, true);

		return redirect()->route('home');
	}
	private function findOrCreateUser($twitterUser)
	{
		$authUser = User::where('twitter_id', $twitterUser->id)->first();
		if ($authUser) {
			return $authUser;
		}
		$avatar_url = $twitterUser->avatar_original;
		$avatar_data = file_get_contents($avatar_url);
		$file_name = sha1(uniqid(rand(), true)) . '.jpg';
		file_put_contents('storage/image' . $file_name,  $avatar_data);
		return User::create([
			'name' => $twitterUser->name,
			'twitter_id' => $twitterUser->id,
			'access_token' => $twitterUser->token,
			'access_token_secret' => $twitterUser->tokenSecret,
			'avatar' => $file_name,
			'profile' => $twitterUser->user['description'],]);
	}

	public function logout()
	{
		Auth::logout();
		return redirect()->route('login');
	}
	public function __construct()
	{
		$this->middleware('guest')->except('logout');
	}
}
