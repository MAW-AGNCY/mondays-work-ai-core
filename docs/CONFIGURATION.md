# Guía de Configuración / Configuration Guide

## Monday's Work AI Core

**Versión:** 1.0.0  
**Licencia:** Proprietary  
**Contacto:** info@mondaysatwork.com

---

## 📋 Requisitos del Sistema / System Requirements

### Mínimos
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Extensiones PHP: curl, json, mbstring

### Recomendados
- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

---

## 🚀 Instalación / Installation

### Español

1. **Descargar el plugin**
   ```bash
   git clone https://github.com/MAW-AGNCY/mondays-work-ai-core.git
   ```

2. **Instalar dependencias**
   ```bash
   cd mondays-work-ai-core
   composer install --no-dev
   ```

3. **Subir a WordPress**
   - Copiar la carpeta a `/wp-content/plugins/`
   - Activar desde el panel de WordPress

4. **Configuración inicial**
   - Ir a **AI Core** > **Configuración**
   - Seleccionar proveedor de IA
   - Ingresar API key

### English

1. **Download plugin**
2. **Install dependencies** with Composer
3. **Upload to WordPress** `/wp-content/plugins/`
4. **Activate** and configure API keys

---

## 🔑 Configuración de Proveedores / Provider Setup

### OpenAI

**Obtener API Key:**
1. Visitar: https://platform.openai.com/api-keys
2. Crear nueva clave secreta
3. Copiar clave (empieza con `sk-`)

**Configurar en el plugin:**
```
Proveedor: OpenAI
API Key: sk-xxxxxxxxxxxxxxxxxxxxxxxx
Modelo: gpt-4 (o gpt-3.5-turbo)
Temperature: 0.7
Max Tokens: 1000
```

### Google Gemini

**Obtener API Key:**
1. Visitar: https://makersuite.google.com/app/apikey
2. Crear API key
3. Copiar clave

**Configurar:**
```
Proveedor: Google Gemini
API Key: AIzaXXXXXXXXXXXXXXXXXXXX
Modelo: gemini-pro
```

### Modelo Local

**Configurar servidor:**
```
Proveedor: Local
API Endpoint: http://localhost:8000/v1
Modelo: llama-2-70b
```

---

## ⚙️ Configuración Avanzada / Advanced Configuration

### Caché

**Recomendado para producción:**
```
Caché habilitado: Sí
Duración: 3600 segundos (1 hora)
```

**Para desarrollo:**
```
Caché habilitado: No
Debug Mode: Sí
```

### Rate Limiting

```
Límite: 60 peticiones/hora
```

Ajustar según plan del proveedor.

---

## 🐛 Resolución de Problemas / Troubleshooting

### Error: "API key inválida"
✅ Verificar formato (OpenAI: `sk-...`)  
✅ Revisar permisos en plataforma del proveedor  
✅ Verificar límites de uso

### Error: "Connection timeout"
✅ Verificar firewall/proxy  
✅ Aumentar timeout en configuración  
✅ Verificar estado del servicio

### Error: "Rate limit exceeded"
✅ Habilitar caché  
✅ Reducir frecuencia de peticiones  
✅ Actualizar plan del proveedor

---

## 📊 Mejores Prácticas / Best Practices

### Performance
- ✅ Habilitar caché en producción
- ✅ Usar rate limiting
- ✅ Monitorear logs

### Seguridad
- ✅ No compartir API keys
- ✅ Usar variables de entorno
- ✅ Revisar permisos de usuario

### Costos
- ✅ Configurar límites de tokens
- ✅ Usar modelos económicos para testing
- ✅ Monitorear uso mensual

---

## ❓ FAQ

**¿Cuánto cuesta usar OpenAI?**  
Depende del modelo. GPT-3.5-turbo: ~$0.002/1K tokens. GPT-4: ~$0.03/1K tokens.

**¿Puedo usar múltiples proveedores?**  
Sí, pero solo uno activo a la vez. Puedes cambiar en configuración.

**¿Funcionan los modelos locales?**  
Sí, pero requieres infraestructura propia (GPU recomendada).

**¿Es compatible con multisite?**  
Sí, configuración independiente por sitio.

---

## 📞 Soporte / Support

**Email:** info@mondaysatwork.com  
**GitHub:** https://github.com/MAW-AGNCY/mondays-work-ai-core  
**Docs:** https://github.com/MAW-AGNCY/mondays-work-ai-core/blob/main/README.md

---

## 📝 Changelog

### 1.0.0 (2025-01-15)
- Initial release
- OpenAI integration
- Google Gemini support
- Local model support
- Cache system
- Rate limiting

---

**© 2025 Mondays at Work. Proprietary License.**
