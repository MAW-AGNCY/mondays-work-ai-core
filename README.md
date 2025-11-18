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

## 🔧 Solución de Problemas

### Error de Parse al Activar el Plugin

Si recibes un error como `Parse error: syntax error, unexpected single-quoted string`, esto ha sido corregido en la última versión. Asegúrate de:

1. Descargar la versión más reciente del repositorio
2. Limpiar cualquier cache de PHP/OPcache
3. Verificar que la sintaxis del archivo `includes/AI/Clients/OpenAIClient.php` es correcta

### Problemas de Compatibilidad con API Keys

El plugin soporta ambos formatos de API Keys de OpenAI:

- **Formato Legacy**: `sk-xxxxxxxxxxxxxxxxxxxxxxxx`
- **Formato Nuevo (Project-based)**: `sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx`

Si tu API key no es reconocida, verifica que:

1. No tiene espacios al inicio o final
2. Corresponde a uno de los formatos soportados
3. La API key está activa en tu cuenta de OpenAI/Gemini

### Plugin No Activa o Muestra Pantalla Blanca

Si el plugin no activa:

1. Verifica que tu servidor cumple con los requisitos mínimos (PHP >= 7.4)
2. Revisa los logs de error de PHP (`/wp-content/debug.log` si WP_DEBUG está activado)
3. Desactiva otros plugins para descartar conflictos
4. Verifica que no hay errores de sintaxis en los archivos PHP

### Problemas con Autoloader

El plugin incluye su propio autoloader PSR-4 personalizado. Si recibes errores de clases no encontradas:

1. Verifica que el archivo `includes/autoload.php` existe y es accesible
2. Comprueba los permisos de archivos (644 para archivos, 755 para directorios)
3. Asegúrate de que la estructura de carpetas está intacta

---

## 🔐 Seguridad y Mejores Prácticas

### Protección de API Keys

Las API keys se almacenan de forma segura:

- ✅ Utilizan funciones de WordPress para almacenamiento encriptado
- ✅ No se exponen en el código fuente del sitio
- ✅ Se validan antes de ser utilizadas
- ✅ Se sanitizan todas las entradas de usuario

### Recomendaciones de Seguridad

1. **No compartas tu API key**: Mantén tus credenciales privadas
2. **Utiliza límites de rate**: Configura límites en tu cuenta de OpenAI/Gemini
3. **Monitorea el uso**: Revisa regularmente el consumo de tu API
4. **Actualiza regularmente**: Mantén el plugin actualizado con las últimas correcciones de seguridad
5. **Usa HTTPS**: Asegúrate de que tu sitio WordPress usa SSL/TLS

### Validación de Entradas

Todas las entradas de usuario son:

- Sanitizadas usando funciones de WordPress (`sanitize_text_field`, etc.)
- Validadas según tipo de dato esperado
- Escapadas antes de mostrarse en HTML
- Protegidas contra inyección SQL usando prepared statements

---

## 📋 Requisitos Técnicos Detallados

### PHP

- **Versión mínima**: PHP 7.4
- **Versión recomendada**: PHP 8.0 o superior
- **Extensiones requeridas**:
  - `json`: Para manejo de respuestas de API
  - `curl`: Para peticiones HTTP a servicios de IA
  - `mbstring`: Para manejo correcto de caracteres multibyte

### WordPress

- **Versión mínima**: WordPress 5.8
- **Versión recomendada**: Última versión estable
- **Características utilizadas**:
  - Options API para configuración
  - Settings API para panel de administración
  - Transients API para caching
  - HTTP API para peticiones externas

### Compatibilidad

- ✅ Compatible con hosting compartido
- ✅ Compatible con WordPress Multisite
- ✅ Compatible con WooCommerce 5.0+
- ✅ Soporta ambos formatos de API keys de OpenAI
- ✅ No requiere Composer en producción

---

## 📝 Changelog Reciente

### Versión Actual (2025-01-27)

#### 🐛 Correcciones de Errores

- **CRÍTICO**: Corregido Parse Error en `OpenAIClient.php` línea 813
  - Eliminada comilla simple duplicada en patrón regex
  - El plugin ahora activa correctamente sin errores de sintaxis

#### ✨ Mejoras

- Verificada compatibilidad con PHP 7.4+
- Actualizada documentación con guía de solución de problemas
- Añadidas mejores prácticas de seguridad
- Documentado soporte para ambos formatos de API keys OpenAI

#### 📚 Documentación

- Añadida sección de troubleshooting completa
- Documentadas características de seguridad
- Actualizados requisitos técnicos detallados
- Añadida información sobre compatibilidad de API keys

---

## 🤝 Contribuyendo al Proyecto

### Reportar Bugs

Si encuentras un error:

1. Verifica que no haya sido reportado ya en [GitHub Issues](https://github.com/MAW-AGNCY/mondays-work-ai-core/issues)
2. Crea un nuevo issue con:
   - Descripción clara del problema
   - Pasos para reproducir el error
   - Versión de PHP y WordPress
   - Logs de error relevantes

### Sugerir Mejoras

Para sugerir nuevas funcionalidades:

1. Abre un issue con la etiqueta "enhancement"
2. Describe claramente la funcionalidad propuesta
3. Explica el caso de uso y beneficios
4. Si es posible, proporciona ejemplos de implementación

### Código de Conducta

- Respeta a todos los colaboradores
- Usa lenguaje inclusivo
- Acepta críticas constructivas
- Enfócate en lo mejor para la comunidad


---

<div align="center">

**[⬆ Volver arriba](#-mondays-work-ai-core)**

Made with ❤️ by Mondays at Work | © 2025 All Rights Reserved

</div>
