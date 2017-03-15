<?php
namespace App\Http\Middleware;

use Closure;

class AuthCustomer
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$cid=$request->get("cid","0");
		if ($cid==2) {
			return response('Unauthorized.', 401);
		}
		return $next($request);
	}
}