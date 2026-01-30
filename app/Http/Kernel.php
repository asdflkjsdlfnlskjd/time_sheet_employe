protected $routeMiddleware = [
// ... другие middleware
'admin.auth' => \App\Http\Middleware\AdminAuth::class,
];
