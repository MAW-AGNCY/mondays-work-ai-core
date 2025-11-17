# 🚀 Monday's Work AI Core

<div align="center">

**Módulo core para plugin WordPress/WooCommerce con integración de IA**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-%3E%3D5.8-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-Proprietary-E31E24)](LICENSE)
[Documentación](docs/CONFIGURATION.md) • [Estructura](STRUCTURE.md) • [Changelog](CHANGELOG.md)

</div>

---

## 📋 Descripción

**Monday's Work AI Core** es un plugin WordPress profesional que proporciona integración modular con múltiples proveedores de inteligencia artificial (OpenAI, Google Gemini, modelos locales). Diseñado con arquitectura MVP, PSR-4 autoloading y mejores prácticas de desarrollo.

### ✨ Características Principales

- 🤖 **Integración Multi-IA**: Soporte para OpenAI, Gemini y modelos locales
- 🏭 **Arquitectura Modular**: Patrón Factory para fácil extensibilidad
- 🔧 **Configuración Intuitiva**: Panel de administración con identidad Mondays at Work
- 📝 **Documentación Bilingüe**: Código comentado en español e inglés
- ⚡ **Rendimiento Optimizado**: Caching, rate limiting y manejo de errores robusto
- 🛡️ **Seguridad**: Validación de entradas, sanitización y WordPress Coding Standards

---

## 🎯 Casos de Uso

- Generación de descripciones de productos para WooCommerce
- Análisis de sentimientos en reseñas
- Chatbot de atención al cliente
- Traducción automática de contenidos
- Generación de contenido de marketing

---

## 📦 Instalación

### Requisitos del Sistema

- PHP >= 7.4
- WordPress >= 5.8
- WooCommerce >= 5.0 (opcional)
- Extensiones PHP: json, curl

### Pasos de Instalación

1. **Clonar el repositorio:**
```bash
git clone https://github.com/MAW-AGNCY/mondays-work-ai-core.git
cd mondays-work-ai-core

> ✅ **Nota:** Este plugin ya no requiere Composer. Incluye autoloader PSR-4 personalizado y funciona en cualquier hosting sin necesidad de instalación de dependencias.
```

2. **Subir al directorio de plugins:**`
```bashcp -r mondays-work-ai-core /path/to/wordpress/wp-content/plugins/
```

3. **Activar desde WordPress:**
   - Ir a 'Plugins > Plugins instalados'   - Buscar "Monday's Work AI Core"
   - Click en "Activar"

---

## ⚙️ Configuración Rápida

### 1. Acceder a la Configuración

Navega a **WordPress Admin > Monday's Work AI > Configuración**

### 2. Configurar API Key

1. Seleccionar proveedor (OpenAI/Gemini/Local)
2. Ingresar tu API Key
3. Click en "Test Connection"
4. Guardar configuración

### 3. Uso Básico

```php
use MondaysWork\AI\Core\AI\AIClientFactory;

// Crear cliente
$client = AIClientFactory::create();

// Generar texto
$response = $client->generateText('Escribe una descripción de producto');
```

📖 **[Guía completa de configuración →](docs/CONFIGURATION.md)**

---

## 🏭 Arquitectura

```
mondays-work-ai-core/
├── includes/
│   ├── Core/              # Núcleo del sistema
│   │   ├── Plugin.php
│   │   ├── Config.php
│   │   ├── Activator.php
│   │   └── Deactivator.php
│   └── AI/                # Módulo de IA
│       ├── AIClientInterface.php
│       ├── AIClientFactory.php
│       └── Clients/
│           ├── OpenAIClient.php
│           ├── GeminiClient.php
│           └── LocalClient.php
├── assets/
│   ├── css/
│   └── js/
├── docs/
└── composer.json
```

📐 **[Estructura detallada →](STRUCTURE.md)**

---

## 🎨 Identidad Visual

El plugin respeta la identidad corporativa de **Mondays at Work**:

- **Color primario**: `#E31E24` (Rojo corporativo)
- **Tipografía**: Sans-serif moderna
- **Estilo**: Minimalista y profesional

---

## 👥 Contribución

Las contribuciones son bienvenidas! Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: Amazing feature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está licenciado bajo GPL v3.0

---

## 🌟 Créditos

Desarrollado con ❤️ por [**Mondays at Work**](https://www.mondaysatwork.com)

---

## 📞 Soporte

- 📧 **Email**: info@mondaysatwork.com
- 🌐 **Web**: [mondaysatwork.com](https://www.mondaysatwork.com)
- 🐛 **Issues**: [GitHub Issues](https://github.com/MAW-AGNCY/mondays-work-ai-core/issues)

---

<div align="center">

**[⬆ Volver arriba](#-mondays-work-ai-core)**

Made with ❤️ by Mondays at Work | © 2025 All Rights Reserved

</div>
