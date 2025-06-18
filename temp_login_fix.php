function handleLogin(): void
{
    try {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        
        $data = ValidationMiddleware::validate($rules);
        
        $userModel = new User();
        $user = $userModel->authenticate($data['email'], $data['password']);
        
        if (!$user) {
            ResponseService::error('Email ou mot de passe incorrect', 401);
        }
        
        $token = JWTManager::generateToken($user);
        
        ResponseService::success([
            'user' => $user,
            'token' => $token,
            'expires_in' => 3600
        ], 'Connexion réussie');
        
    } catch (\Exception $e) {
        ResponseService::error('Login error: ' . $e->getMessage(), 500);
    }
}