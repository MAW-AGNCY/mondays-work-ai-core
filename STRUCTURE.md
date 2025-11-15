# Monday's Work AI Core - Project Structure

Estructura de carpetas y archivos principales para el núcleo del plugin de IA.

## Estructura de Directorios

```
mondays-work-ai-core/
│
├── mondays-work-ai-core.php          # Bootstrap principal del plugin
├── composer.json                      # Dependencias y autoload PSR-4
├── package.json                       # Dependencias JavaScript (opcional)
├── .gitignore                        # Archivos ignorados por Git
├── README.md                         # Documentación principal
├── STRUCTURE.md                      # Este archivo
├── CHANGELOG.md                      # Historial de cambios
│
├── includes/                         # Clases principales del plugin
│   ├── Core/                        # Núcleo del sistema
│   │   ├── Plugin.php              # Clase principal Singleton
│   │   ├── Activator.php           # Lógica de activación
│   │   ├── Deactivator.php         # Lógica de desactivación
│   │   └── Config.php              # Gestión de configuración
│   │
│   ├── AI/                          # Módulo de IA
│   │   ├── AIClientInterface.php   # Interfaz del cliente IA
│   │   ├── AIClientFactory.php     # Factory para crear clientes
│   │   ├── Clients/                # Implementaciones de proveedores
│   │   │   ├── OpenAIClient.php   # Cliente para OpenAI
│   │   │   ├── GeminiClient.php   # Cliente para Google Gemini
│   │   │   └── LocalClient.php    # Cliente para modelos locales
│   │   └── PromptBuilder.php       # Constructor de prompts
│   │
│   ├── WooCommerce/                 # Integración con WooCommerce
│   │   ├── WC_Helper.php           # Funciones auxiliares
│   │   ├── ProductGenerator.php    # Generador de descripciones
│   │   └── MetaBoxes.php           # MetaBoxes en admin
│   │
│   ├── Admin/                       # Panel de administración
│   │   ├── SettingsPage.php        # Página de configuración
│   │   ├── Dashboard.php           # Dashboard del plugin
│   │   └── Assets.php              # Gestión de CSS/JS admin
│   │
│   ├── API/                         # REST API endpoints
│   │   ├── RestController.php      # Controlador principal REST
│   │   └── Endpoints/              # Endpoints específicos
│   │       ├── GenerateText.php   # Endpoint generar texto
│   │       └── TestConnection.php  # Endpoint test conexión
│   │
│   └── Utils/                       # Utilidades y helpers
│       ├── Logger.php              # Sistema de logs
│       ├── Validator.php           # Validación de datos
│       └── Cache.php               # Gestión de caché
│
├── assets/                           # Assets frontend y backend
│   ├── css/                        # Hojas de estilo
│   │   ├── admin.css              # Estilos del admin
│   │   └── frontend.css           # Estilos del frontend
│   ├── js/                         # Scripts JavaScript
│   │   ├── admin.js               # JavaScript del admin
│   │   └── frontend.js            # JavaScript del frontend
│   └── images/                     # Imágenes y iconos
│       └── icon.png               # Icono del plugin
│
├── templates/                        # Plantillas PHP
│   ├── admin/                      # Plantillas del admin
│   │   ├── settings-page.php      # Vista de ajustes
│   │   └── dashboard.php          # Vista del dashboard
│   └── public/                     # Plantillas públicas
│       └── shortcode-output.php   # Salida de shortcodes
│
├── languages/                        # Archivos de traducción
│   └── mondays-work-ai-core.pot    # Plantilla de traducción
│
├── tests/                            # Tests unitarios y de integración
│   ├── bootstrap.php               # Bootstrap de tests
│   ├── Unit/                       # Tests unitarios
│   │   ├── PluginTest.php
│   │   └── AI/
│   │       └── OpenAIClientTest.php
│   └── Integration/                # Tests de integración
│       └── WooCommerceTest.php
│
└── vendor/                           # Dependencias de Composer (generado)
    └── autoload.php
```

## Archivos Principales

### 1. `mondays-work-ai-core.php`
Archivo principal del plugin que WordPress detecta. Contiene:
- Header del plugin
- Definición de constantes
- Carga del autoloader
- Inicialización del plugin
- Hooks de activación/desactivación

### 2. `includes/Core/Plugin.php`
Clase principal Singleton que:
- Inicializa todos los módulos
- Registra hooks de WordPress
- Expone servicios a otros módulos
- Gestiona el ciclo de vida del plugin

### 3. `includes/AI/AIClientInterface.php`
Interfaz que define métodos comunes:
```php
interface AIClientInterface {
    public function generateText(string $prompt, array $params): string;
    public function chat(array $messages, array $params): string;
    public function analyzeText(string $text): array;
    public function testConnection(): bool;
}
```

### 4. `includes/AI/Clients/OpenAIClient.php`
Implementación concreta para OpenAI:
- Gestión de API key
- Construcción de peticiones
- Manejo de respuestas y errores
- Rate limiting

### 5. `includes/WooCommerce/ProductGenerator.php`
Generador de contenido para productos:
- Descripciones optimizadas
- Títulos SEO
- Meta descriptions
- Tags y categorías sugeridas

### 6. `includes/Admin/SettingsPage.php`
Página de configuración con:
- Formulario de API keys
- Selección de proveedor
- Parámetros globales (temperatura, modelo, etc.)
- Test de conexión

### 7. `includes/API/RestController.php`
Controlador REST API:
- Registra endpoints personalizados
- Valida permisos y nonces
- Sanitiza input/output

## Convenciones de Código

### Namespaces
```php
namespace MondaysWork\AI\Core;           // Núcleo
namespace MondaysWork\AI\Core\AI;       // Módulo IA
namespace MondaysWork\AI\Core\Admin;    // Admin
```

### Prefijos
- **Funciones globales**: `mwai_`
- **Opciones WordPress**: `mwai_`
- **Constantes**: `MWAI_`
- **Hooks (actions/filters)**: `mwai_`

### PSR-4 Autoload
```json
"autoload": {
    "psr-4": {
        "MondaysWork\\AI\\Core\\": "includes/"
    }
}
```

## Requisitos del MVP

### Archivos Esenciales
1. **mondays-work-ai-core.php** - Bootstrap
2. **includes/Core/Plugin.php** - Clase principal
3. **includes/Core/Config.php** - Configuración
4. **includes/AI/AIClientInterface.php** - Interfaz IA
5. **includes/AI/Clients/OpenAIClient.php** - Cliente OpenAI
6. **includes/WooCommerce/ProductGenerator.php** - Funcionalidad estrella
7. **includes/Admin/SettingsPage.php** - Ajustes
8. **composer.json** - Dependencias

### Archivos Opcionales (Post-MVP)
- Tests completos
- Múltiples clientes IA
- Dashboard avanzado
- Shortcodes y bloques Gutenberg
- Caché y optimizaciones

## Próximos Pasos

1. Crear `composer.json` con autoload PSR-4
2. Implementar clases del núcleo (Plugin, Config, Activator)
3. Desarrollar AIClientInterface y OpenAIClient
4. Crear WooCommerce ProductGenerator
5. Desarrollar SettingsPage
6. Tests y refinamiento

---

**Versión**: 0.1.0  
**Última actualización**: 2025-11-15
