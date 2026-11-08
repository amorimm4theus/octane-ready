# Lumen -> Laravel mapping

| Lumen | Laravel 11+ |
|---|---|
| `$app->withFacades()` | always on |
| `$app->withEloquent()` | always on |
| `$app->configure('x')` | `config/x.php` is loaded automatically |
| `$app->middleware([...])` | `->withMiddleware(fn ($m) => $m->append(...))` (or `$m->api(append: ...)`) |
| `$app->routeMiddleware([...])` | `$middleware->alias([...])` |
| `$app->register(Provider::class)` | `bootstrap/providers.php` |
| `$app->singleton(...)` in bootstrap | `AppServiceProvider::register()` |
| `$router->get('/x/{id:[0-9]+}', 'C@m')` | `Route::get('/x/{id}', [C::class, 'm'])->whereNumber('id')` |
| `$router->group(['prefix' => 'v1'], ...)` | `Route::prefix('v1')->group(...)` |
| `$this->validate($request, $rules)` | `$request->validate($rules)` or `ValidatesRequests` trait |
| `Laravel\Lumen\Routing\Controller` | `App\Http\Controllers\Controller` |
| `AuthServiceProvider` `viaRequest('api', ...)` | custom guard (`Auth::viaRequest`) or Sanctum |
| `abort()` renders HTML | `shouldRenderJsonWhen` in `bootstrap/app.php` for JSON APIs |
| `env()` in application code | `config()`; `env()` only inside `config/*.php` |
| Helpers missing in Lumen (`abort_if`, `to_route`, ...) | available |
| `Laravel\Lumen\Testing\TestCase` | `Tests\TestCase` (`$this->get` -> `$this->getJson`) |

## Octane red flags in legacy code

- `static` properties holding tenant, user, locale, request or results
- singletons built with `$app['request']`, `auth()->user()` or per-tenant config
- static arrays used as caches with no size limit
- `$_SERVER` / `$_GET` read directly
