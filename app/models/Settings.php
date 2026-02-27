<?php
namespace App\Models;

class Settings {
    private static $table = 'settings';
    
    public static function get(string $key, $default = null) {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM " . self::$table . " WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    }
    
    public static function set(string $key, string $value): bool {
        $db = \Database::getConnection();
        
        $stmt = $db->prepare("SELECT id FROM " . self::$table . " WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $db->prepare("UPDATE " . self::$table . " SET setting_value = ? WHERE setting_key = ?");
            return $stmt->execute([$value, $key]);
        } else {
            $stmt = $db->prepare("INSERT INTO " . self::$table . " (setting_key, setting_value) VALUES (?, ?)");
            return $stmt->execute([$key, $value]);
        }
    }
    
    public static function delete(string $key): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM " . self::$table . " WHERE setting_key = ?");
        return $stmt->execute([$key]);
    }
    
    public static function getAll(): array {
        $db = \Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM " . self::$table);
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
    
    public static function getMetaApi(): array {
        return [
            'app_id' => self::get('meta_app_id', ''),
            'app_secret' => self::get('meta_app_secret', ''),
            'access_token' => self::get('meta_access_token', ''),
            'ad_account_id' => self::get('meta_ad_account_id', ''),
            'status' => self::get('meta_status', 'disconnected'),
            'last_test' => self::get('meta_last_test', '')
        ];
    }
    
    public static function setMetaApi(array $data): bool {
        if (!empty($data['app_id'])) self::set('meta_app_id', $data['app_id']);
        if (!empty($data['app_secret'])) self::set('meta_app_secret', $data['app_secret']);
        if (!empty($data['access_token'])) self::set('meta_access_token', $data['access_token']);
        if (!empty($data['ad_account_id'])) self::set('meta_ad_account_id', $data['ad_account_id']);
        return true;
    }
    
    public static function getOpenAI(): array {
        return [
            'api_key' => self::get('openai_api_key', ''),
            'image_model' => self::get('openai_image_model', 'dall-e-3'),
            'gpt_model' => self::get('openai_gpt_model', 'gpt-4o'),
            'status' => self::get('openai_status', 'disconnected'),
            'last_test' => self::get('openai_last_test', '')
        ];
    }
    
    public static function setOpenAI(array $data): bool {
        if (!empty($data['api_key'])) self::set('openai_api_key', $data['api_key']);
        if (!empty($data['image_model'])) self::set('openai_image_model', $data['image_model']);
        if (!empty($data['gpt_model'])) self::set('openai_gpt_model', $data['gpt_model']);
        return true;
    }
    
    public static function getStripe(): array {
        return [
            'publishable_key' => self::get('stripe_publishable_key', ''),
            'secret_key' => self::get('stripe_secret_key', ''),
            'webhook_secret' => self::get('stripe_webhook_secret', ''),
            'status' => self::get('stripe_status', 'disconnected')
        ];
    }
    
    public static function setStripe(array $data): bool {
        if (!empty($data['publishable_key'])) self::set('stripe_publishable_key', $data['publishable_key']);
        if (!empty($data['secret_key'])) self::set('stripe_secret_key', $data['secret_key']);
        if (!empty($data['webhook_secret'])) self::set('stripe_webhook_secret', $data['webhook_secret']);
        return true;
    }
    
    public static function getUnsplash(): array {
        return [
            'api_key' => self::get('unsplash_api_key', ''),
            'status' => self::get('unsplash_status', 'disconnected'),
            'last_test' => self::get('unsplash_last_test', '')
        ];
    }
    
    public static function setUnsplash(array $data): bool {
        if (!empty($data['api_key'])) self::set('unsplash_api_key', $data['api_key']);
        return true;
    }
    
    public static function getBriefingPrompt(): string {
        return self::get('briefing_prompt', 'Eres un experto en marketing y branding. Basándote en la siguiente descripción de un negocio, crea un briefing de marca completo y detallado que incluya:

1. Nombre de la marca
2. Descripción de la empresa (misión, visión)
3. Productos/Servicios principales
4. Propuesta de valor única
5. Público objetivo
6. Tono y personalidad de marca
7. Colores recomendados
8. Estilo visual sugerido
9. Mensajes clave para publicidad
10. Sugerencias para creatividades publicitarias

Descripción del negocio: {description}

Proporciona un briefing completo y profesional en español.');
    }
    
    public static function setBriefingPrompt(string $prompt): bool {
        return self::set('briefing_prompt', $prompt);
    }
}
