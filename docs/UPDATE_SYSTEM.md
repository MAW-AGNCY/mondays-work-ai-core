# Plugin Update System / Sistema de Actualizaciones del Plugin

## English

### Overview

This document describes the update management system for Monday's Work AI Core plugin using Siteground hosting infrastructure.

### Architecture

The update system uses a **hybrid approach** to maintain a public GitHub repository while providing secure, controlled updates:

- **Public Repository**: GitHub hosts the source code publicly for transparency and collaboration
- **Private Update Server**: Siteground hosts the update metadata and distribution files
- **WordPress Integration**: Custom update checker integrates with WordPress's native update system

### Why This Approach?

1. **Zero Additional Costs**: Uses existing Siteground infrastructure
2. **No GitHub Pro Required**: Keeps repository public without paying €4/month
3. **Full Control**: Complete control over update distribution and timing
4. **Security**: API keys and sensitive data never exposed in public repo
5. **Flexibility**: Can restrict updates to authorized clients only

### Components

#### 1. Update Checker (WordPress Plugin)

Location: `includes/Core/UpdateChecker.php`

Responsibilities:
- Checks Siteground server for new versions
- Downloads update packages
- Integrates with WordPress update UI
- Validates update authenticity

#### 2. Update Server (Siteground)

Location: Your Siteground hosting account

Files needed:
- `update-info.json` - Version metadata
- `mondays-work-ai-core-{version}.zip` - Plugin packages
- `.htaccess` - Access control (optional)

#### 3. Version Metadata Format

```json
{
  "name": "Monday's Work AI Core",
  "slug": "mondays-work-ai-core",
  "version": "1.0.0",
  "download_url": "https://yourdomain.com/updates/mondays-work-ai-core-1.0.0.zip",
  "requires": "6.0",
  "tested": "6.4",
  "requires_php": "8.0",
  "last_updated": "2025-01-15 12:00:00",
  "sections": {
    "description": "Core module for WordPress/WooCommerce with AI integration",
    "changelog": "<h4>1.0.0</h4><ul><li>Initial release</li></ul>"
  },
  "author": "Mondays at Work",
  "author_profile": "https://mondaysatwork.com",
  "banners": {
    "low": "https://yourdomain.com/assets/banner-772x250.jpg",
    "high": "https://yourdomain.com/assets/banner-1544x500.jpg"
  }
}
```

### Setup Instructions

#### Step 1: Prepare Siteground Server

1. Create directory structure:
```
/public_html/plugin-updates/
├── update-info.json
├── releases/
│   ├── mondays-work-ai-core-1.0.0.zip
│   └── mondays-work-ai-core-1.0.1.zip
└── .htaccess (optional)
```

2. Upload `update-info.json` with current version information

3. Optional: Add `.htaccess` for IP whitelisting or API key authentication:
```apache
# Require API key in query string
RewriteEngine On
RewriteCond %{QUERY_STRING} !api_key=YOUR_SECRET_KEY
RewriteRule ^update-info\.json$ - [F,L]
```

#### Step 2: Create Plugin Packages

When releasing a new version:

1. Export clean copy from GitHub:
```bash
git archive --format=zip --output=mondays-work-ai-core-1.0.0.zip main
```

2. Ensure package structure:
```
mondays-work-ai-core/
├── includes/
├── assets/
├── mondays-work-ai-core.php
├── composer.json
└── LICENSE
```

3. Upload to Siteground: `/public_html/plugin-updates/releases/`

#### Step 3: Implement Update Checker

The UpdateChecker class will be added to handle automatic updates:

- Hooks into WordPress's `pre_set_site_transient_update_plugins` filter
- Checks remote server every 12 hours
- Compares versions using PHP's `version_compare()`
- Displays update notifications in WordPress admin

#### Step 4: Update Workflow

When releasing a new version:

1. **Commit to GitHub**:
   - Update version in `mondays-work-ai-core.php` header
   - Update `composer.json` version
   - Create git tag: `git tag v1.0.1`
   - Push: `git push origin main --tags`

2. **Create Release Package**:
   - Export from GitHub or build locally
   - Test package installation
   - Upload to Siteground

3. **Update Metadata**:
   - Edit `update-info.json` on Siteground
   - Update version number
   - Update download URL
   - Add changelog entries

4. **Notify Clients** (optional):
   - Clients with plugin installed will see update automatically
   - Send email notification if desired

### Security Considerations

1. **API Key Authentication**: Add API key requirement to `update-info.json` endpoint
2. **SSL/HTTPS**: Always use HTTPS for update server
3. **Package Verification**: Consider adding checksum/signature validation
4. **Access Logs**: Monitor Siteground access logs for suspicious activity
5. **Rate Limiting**: Implement rate limiting if needed

### Version Management Strategy

#### Semantic Versioning
Follow semver.org: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes, incompatible API changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

Examples:
- `1.0.0` - Initial release
- `1.0.1` - Bug fix
- `1.1.0` - New AI provider added
- `2.0.0` - Major architecture change

#### Release Channels (Future)

Consider implementing:
- **Stable**: Production-ready releases
- **Beta**: Testing releases for select clients
- **Dev**: Development snapshots (not distributed)

### Backup Strategy

1. **GitHub**: Source of truth for code
2. **Siteground**: Weekly backups of update server files
3. **Local**: Keep local copies of all release packages

### Monitoring and Analytics

1. Track update server requests in Siteground analytics
2. Monitor error logs for failed updates
3. Track version adoption rates

### Contact

For update system support: info@mondaysatwork.com

---

## Español

### Descripción General

Este documento describe el sistema de gestión de actualizaciones para el plugin Monday's Work AI Core utilizando la infraestructura de hosting de Siteground.

### Arquitectura

El sistema de actualizaciones utiliza un **enfoque híbrido** para mantener un repositorio público en GitHub mientras proporciona actualizaciones seguras y controladas:

- **Repositorio Público**: GitHub aloja el código fuente públicamente para transparencia y colaboración
- **Servidor de Actualizaciones Privado**: Siteground aloja los metadatos de actualización y archivos de distribución
- **Integración WordPress**: Verificador de actualizaciones personalizado se integra con el sistema nativo de WordPress

### ¿Por Qué Este Enfoque?

1. **Cero Costos Adicionales**: Utiliza infraestructura existente de Siteground
2. **No Requiere GitHub Pro**: Mantiene repositorio público sin pagar €4/mes
3. **Control Total**: Control completo sobre distribución y temporización de actualizaciones
4. **Seguridad**: Claves API y datos sensibles nunca se exponen en repositorio público
5. **Flexibilidad**: Puede restringir actualizaciones solo a clientes autorizados

### Componentes

#### 1. Verificador de Actualizaciones (Plugin WordPress)

Ubicación: `includes/Core/UpdateChecker.php`

Responsabilidades:
- Verifica servidor Siteground para nuevas versiones
- Descarga paquetes de actualización
- Se integra con UI de actualizaciones de WordPress
- Valida autenticidad de actualizaciones

#### 2. Servidor de Actualizaciones (Siteground)

Ubicación: Tu cuenta de hosting Siteground

Archivos necesarios:
- `update-info.json` - Metadatos de versión
- `mondays-work-ai-core-{version}.zip` - Paquetes del plugin
- `.htaccess` - Control de acceso (opcional)

#### 3. Formato de Metadatos de Versión

Ver sección en inglés para formato JSON.

### Instrucciones de Configuración

#### Paso 1: Preparar Servidor Siteground

1. Crear estructura de directorios:
```
/public_html/plugin-updates/
├── update-info.json
├── releases/
│   ├── mondays-work-ai-core-1.0.0.zip
│   └── mondays-work-ai-core-1.0.1.zip
└── .htaccess (opcional)
```

2. Subir `update-info.json` con información de versión actual

3. Opcional: Añadir `.htaccess` para lista blanca de IPs o autenticación con clave API

#### Paso 2: Crear Paquetes del Plugin

Al lanzar una nueva versión:

1. Exportar copia limpia desde GitHub:
```bash
git archive --format=zip --output=mondays-work-ai-core-1.0.0.zip main
```

2. Subir a Siteground: `/public_html/plugin-updates/releases/`

#### Paso 3: Implementar Verificador de Actualizaciones

La clase UpdateChecker se añadirá para manejar actualizaciones automáticas.

#### Paso 4: Flujo de Actualización

Al lanzar una nueva versión:

1. **Commit a GitHub**:
   - Actualizar versión en encabezado de `mondays-work-ai-core.php`
   - Actualizar versión en `composer.json`
   - Crear tag git: `git tag v1.0.1`
   - Push: `git push origin main --tags`

2. **Crear Paquete de Lanzamiento**:
   - Exportar desde GitHub o construir localmente
   - Probar instalación del paquete
   - Subir a Siteground

3. **Actualizar Metadatos**:
   - Editar `update-info.json` en Siteground
   - Actualizar número de versión
   - Actualizar URL de descarga
   - Añadir entradas de changelog

4. **Notificar Clientes** (opcional):
   - Clientes con plugin instalado verán actualización automáticamente
   - Enviar notificación por email si se desea

### Consideraciones de Seguridad

1. **Autenticación con Clave API**: Añadir requisito de clave API al endpoint `update-info.json`
2. **SSL/HTTPS**: Siempre usar HTTPS para servidor de actualizaciones
3. **Verificación de Paquetes**: Considerar añadir validación de checksum/firma
4. **Logs de Acceso**: Monitorear logs de acceso de Siteground para actividad sospechosa
5. **Limitación de Tasa**: Implementar rate limiting si es necesario

### Estrategia de Gestión de Versiones

#### Versionado Semántico
Seguir semver.org: `MAJOR.MINOR.PATCH`

- **MAJOR**: Cambios incompatibles, cambios de API incompatibles
- **MINOR**: Nuevas características, compatible hacia atrás
- **PATCH**: Corrección de errores, compatible hacia atrás

Ejemplos:
- `1.0.0` - Lanzamiento inicial
- `1.0.1` - Corrección de errores
- `1.1.0` - Nuevo proveedor de IA añadido
- `2.0.0` - Cambio importante de arquitectura

### Estrategia de Respaldo

1. **GitHub**: Fuente de verdad para el código
2. **Siteground**: Respaldos semanales de archivos del servidor de actualizaciones
3. **Local**: Mantener copias locales de todos los paquetes de lanzamiento

### Monitoreo y Análisis

1. Rastrear solicitudes al servidor de actualizaciones en analytics de Siteground
2. Monitorear logs de errores para actualizaciones fallidas
3. Rastrear tasas de adopción de versiones

### Contacto

Para soporte del sistema de actualizaciones: info@mondaysatwork.com
