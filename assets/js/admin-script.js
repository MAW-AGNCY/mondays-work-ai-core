/**
 * Admin JavaScript - Monday's Work AI Core
 * JavaScript de Administración - Monday's Work AI Core
 *
 * Interactive functionality for the admin interface with accessibility support.
 * Funcionalidad interactiva para la interfaz administrativa con soporte de accesibilidad.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Assets
 * @since      1.0.0
 * @author     Mondays at Work <info@mondaysatwork.com>
 * @license    Proprietary
 */

(function($) {
    'use strict';

    /**
     * Main Admin Object
     * Objeto Principal de Administración
     */
    const MWAIAdmin = {

        /**
         * Initialize / Inicializar
         */
        init: function() {
            this.initTabs();
            this.initFormValidation();
            this.initConnectionTest();
            this.initAjaxSave();
            this.initConfirmDialogs();
            this.initTooltips();
            
            console.log('Monday\'s Work AI Core Admin initialized');
        },

        /**
         * Initialize tab navigation
         * Inicializa navegación por pestañas
         */
        initTabs: function() {
            const tabs = $('.mwai-tab');
            const tabContents = $('.mwai-tab-content');

            // Handle tab clicks / Manejar clics en pestañas
            tabs.on('click', function(e) {
                const $this = $(this);
                const tabId = $this.data('tab');

                // Update active states / Actualizar estados activos
                tabs.removeClass('mwai-tab-active');
                $this.addClass('mwai-tab-active');

                // Update ARIA attributes / Actualizar atributos ARIA
                tabs.attr('aria-selected', 'false');
                $this.attr('aria-selected', 'true');

                // Show corresponding content / Mostrar contenido correspondiente
                tabContents.hide();
                $('#mwai-tab-' + tabId).fadeIn(300);
            });

            // Keyboard navigation / Navegación por teclado
            tabs.on('keydown', function(e) {
                let $next;

                // Arrow right / Flecha derecha
                if (e.keyCode === 39) {
                    $next = $(this).next('.mwai-tab');
                    if ($next.length === 0) {
                        $next = tabs.first();
                    }
                }

                // Arrow left / Flecha izquierda
                if (e.keyCode === 37) {
                    $next = $(this).prev('.mwai-tab');
                    if ($next.length === 0) {
                        $next = tabs.last();
                    }
                }

                if ($next) {
                    $next.focus().click();
                    e.preventDefault();
                }
            });
        },

        /**
         * Initialize form validation
         * Inicializa validación de formularios
         */
        initFormValidation: function() {
            const self = this;

            // API Key validation / Validación de API Key
            $('input[name*="[api_key]"]').on('blur', function() {
                const $input = $(this);
                const value = $input.val().trim();
                const provider = $('#mwai_provider').val();

                if (value) {
                    self.validateApiKey(value, provider, $input);
                }
            });

            // Temperature validation / Validación de temperatura
            $('input[name*="[temperature]"]').on('input', function() {
                const value = parseFloat($(this).val());
                if (value < 0 || value > 2) {
                    self.showFieldError($(this), mwaiAdmin.i18n.error + ': Valor debe estar entre 0 y 2');
                } else {
                    self.clearFieldError($(this));
                }
            });

            // Max tokens validation / Validación de tokens máximos
            $('input[name*="[max_tokens]"]').on('input', function() {
                const value = parseInt($(this).val());
                if (value < 50 || value > 32000) {
                    self.showFieldError($(this), mwaiAdmin.i18n.error + ': Valor debe estar entre 50 y 32000');
                } else {
                    self.clearFieldError($(this));
                }
            });

            // Form submit validation / Validación al enviar formulario
            $('.mwai-form').on('submit', function(e) {
                const $form = $(this);
                const hasErrors = $form.find('.mwai-field-error').length > 0;

                if (hasErrors) {
                    e.preventDefault();
                    self.showNotice(
                        'Por favor, corrige los errores antes de guardar',
                        'error'
                    );
                    return false;
                }
            });
        },

        /**
         * Validate API key format
         * Valida formato de API key
         * 
         * @param {string} apiKey - API key to validate / API key a validar
         * @param {string} provider - Provider name / Nombre del proveedor
         * @param {jQuery} $input - Input element / Elemento de entrada
         */
        validateApiKey: function(apiKey, provider, $input) {
            let isValid = false;
            let message = '';

            switch (provider) {
                case 'openai':
                    // OpenAI keys start with "sk-"
                    isValid = /^sk-[a-zA-Z0-9]{20,}$/.test(apiKey);
                    message = 'API key de OpenAI inválida. Debe comenzar con "sk-"';
                    break;

                case 'gemini':
                    // Google Gemini keys are alphanumeric
                    isValid = /^[a-zA-Z0-9_-]{20,}$/.test(apiKey);
                    message = 'API key de Gemini inválida';
                    break;

                case 'local':
                    // Local models might not need specific format
                    isValid = apiKey.length > 0;
                    break;
            }

            if (!isValid && message) {
                this.showFieldError($input, message);
            } else {
                this.clearFieldError($input);
            }

            return isValid;
        },

        /**
         * Initialize connection test functionality
         * Inicializa funcionalidad de prueba de conexión
         */
        initConnectionTest: function() {
            const self = this;

            $('.mwai-test-connection').on('click', function(e) {
                e.preventDefault();

                const $button = $(this);
                const provider = $button.data('provider');
                const $statusDiv = $('.mwai-connection-status');

                // Disable button / Deshabilitar botón
                $button.prop('disabled', true);
                
                // Show loading state / Mostrar estado de carga
                $statusDiv
                    .removeClass('success error')
                    .addClass('active loading')
                    .html('<span class="mwai-spinner"></span> ' + mwaiAdmin.i18n.testing);

                // Make AJAX request / Realizar petición AJAX
                $.ajax({
                    url: mwaiAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mwai_test_connection',
                        nonce: mwaiAdmin.nonce,
                        provider: provider
                    },
                    success: function(response) {
                        if (response.success) {
                            $statusDiv
                                .removeClass('loading error')
                                .addClass('success')
                                .html('✓ ' + mwaiAdmin.i18n.connectionOk);
                        } else {
                            $statusDiv
                                .removeClass('loading success')
                                .addClass('error')
                                .html('✗ ' + (response.data.message || mwaiAdmin.i18n.connectionFailed));
                        }
                    },
                    error: function(xhr, status, error) {
                        $statusDiv
                            .removeClass('loading success')
                            .addClass('error')
                            .html('✗ ' + mwaiAdmin.i18n.error + ': ' + error);
                    },
                    complete: function() {
                        // Re-enable button / Re-habilitar botón
                        $button.prop('disabled', false);

                        // Hide status after 5 seconds / Ocultar estado después de 5 segundos
                        setTimeout(function() {
                            $statusDiv.removeClass('active');
                        }, 5000);
                    }
                });
            });
        },

        /**
         * Initialize AJAX save functionality
         * Inicializa funcionalidad de guardado AJAX
         */
        initAjaxSave: function() {
            const self = this;

            // Optional: Add AJAX save button
            // Opcional: Agregar botón de guardado AJAX
            $('.mwai-ajax-save').on('click', function(e) {
                e.preventDefault();

                const $button = $(this);
                const $form = $button.closest('form');
                const formData = $form.serializeArray();

                // Convert to object / Convertir a objeto
                const settings = {};
                formData.forEach(function(item) {
                    if (item.name.includes('[')) {
                        const key = item.name.match(/\[([^\]]+)\]/)[1];
                        settings[key] = item.value;
                    }
                });

                // Show saving state / Mostrar estado de guardado
                $button
                    .prop('disabled', true)
                    .text(mwaiAdmin.i18n.saving);

                $.ajax({
                    url: mwaiAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mwai_save_settings',
                        nonce: mwaiAdmin.nonce,
                        settings: settings
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotice(
                                mwaiAdmin.i18n.saved,
                                'success'
                            );
                        } else {
                            self.showNotice(
                                response.data.message || mwaiAdmin.i18n.error,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        self.showNotice(
                            mwaiAdmin.i18n.error,
                            'error'
                        );
                    },
                    complete: function() {
                        $button
                            .prop('disabled', false)
                            .text('Guardar Cambios');
                    }
                });
            });
        },

        /**
         * Initialize confirm dialogs
         * Inicializa diálogos de confirmación
         */
        initConfirmDialogs: function() {
            $('.mwai-confirm-action').on('click', function(e) {
                const message = $(this).data('confirm') || mwaiAdmin.i18n.confirmDelete;
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Initialize tooltips
         * Inicializa tooltips
         */
        initTooltips: function() {
            // Add simple tooltip functionality / Agregar funcionalidad simple de tooltip
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltipText = $element.data('tooltip');

                $element.on('mouseenter focus', function() {
                    const $tooltip = $('<div class="mwai-tooltip">' + tooltipText + '</div>');
                    $('body').append($tooltip);

                    const offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 10,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });

                    $tooltip.fadeIn(200);
                });

                $element.on('mouseleave blur', function() {
                    $('.mwai-tooltip').fadeOut(200, function() {
                        $(this).remove();
                    });
                });
            });
        },

        /**
         * Show notification message
         * Muestra mensaje de notificación
         * 
         * @param {string} message - Message to display / Mensaje a mostrar
         * @param {string} type - Type: success, error, warning, info
         */
        showNotice: function(message, type) {
            type = type || 'info';

            const icons = {
                success: 'yes',
                error: 'no',
                warning: 'warning',
                info: 'info'
            };

            const $notice = $('<div class="mwai-notice mwai-notice-' + type + '">')
                .html(
                    '<span class="dashicons dashicons-' + icons[type] + '"></span>' +
                    '<span>' + message + '</span>'
                );

            // Insert before form or at top / Insertar antes del formulario o al inicio
            if ($('.mwai-form').length) {
                $('.mwai-form').first().before($notice);
            } else {
                $('.mwai-tab-content').prepend($notice);
            }

            // Auto-hide after 5 seconds / Auto-ocultar después de 5 segundos
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to notice / Desplazar a notificación
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        },

        /**
         * Show field error
         * Muestra error de campo
         * 
         * @param {jQuery} $field - Field element / Elemento de campo
         * @param {string} message - Error message / Mensaje de error
         */
        showFieldError: function($field, message) {
            // Remove existing error / Eliminar error existente
            this.clearFieldError($field);

            // Add error class / Agregar clase de error
            $field.addClass('mwai-field-error');

            // Add error message / Agregar mensaje de error
            const $error = $('<p class="mwai-error-message">' + message + '</p>');
            $field.after($error);

            // Update ARIA / Actualizar ARIA
            $field.attr('aria-invalid', 'true');
        },

        /**
         * Clear field error
         * Limpia error de campo
         * 
         * @param {jQuery} $field - Field element / Elemento de campo
         */
        clearFieldError: function($field) {
            $field.removeClass('mwai-field-error');
            $field.next('.mwai-error-message').remove();
            $field.attr('aria-invalid', 'false');
        },

        /**
         * Debounce function
         * Función de debounce
         * 
         * @param {function} func - Function to debounce / Función a debounce
         * @param {number} wait - Wait time in ms / Tiempo de espera en ms
         * @return {function}
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Document ready / Documento listo
     */
    $(document).ready(function() {
        MWAIAdmin.init();
    });

    /**
     * Expose to global scope / Exponer al ámbito global
     */
    window.MWAIAdmin = MWAIAdmin;

})(jQuery);
