/**
 * School Sports API Admin JavaScript
 *
 * Handles the admin-facing functionality, including the shortcode button.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

(function($) {
    'use strict';

    /**
     * Shortcode Button
     */
    tinymce.create('tinymce.plugins.SchoolSportsAPIShortcode', {
        init: function(editor, url) {
            editor.addButton('school_sports_api_shortcode', {
                title: 'School Sports API Shortcode',
                icon: 'code',
                onclick: function() {
                    // Show shortcode popup
                    $('#school-sports-api-shortcode-popup').show();
                    
                    // Initialize shortcode preview
                    updateShortcodePreview();
                }
            });
        },
        createControl: function(n, cm) {
            return null;
        },
    });
    
    tinymce.PluginManager.add('school_sports_api_shortcode', tinymce.plugins.SchoolSportsAPIShortcode);
    
    /**
     * Shortcode Generator
     */
    $(document).ready(function() {
        // Update shortcode preview on change
        $('#school-sports-api-shortcode-type, #school-sports-api-sport, #school-sports-api-school-type, #school-sports-api-school-year, #school-sports-api-live-school-type, #school-sports-api-refresh-interval').on('change input', updateShortcodePreview);
        
        // Toggle options based on shortcode type
        $('#school-sports-api-shortcode-type').on('change', function() {
            if ($(this).val() === 'live') {
                $('#school-sports-api-results-options').hide();
                $('#school-sports-api-live-options').show();
            } else {
                $('#school-sports-api-results-options').show();
                $('#school-sports-api-live-options').hide();
            }
            updateShortcodePreview();
        });
        
        // Insert shortcode button
        $('#school-sports-api-insert-shortcode').on('click', function() {
            var shortcode = $('#school-sports-api-shortcode-preview').val();
            tinymce.activeEditor.execCommand('mceInsertContent', false, shortcode);
            $('#school-sports-api-shortcode-popup').hide();
        });
        
        // Cancel button
        $('#school-sports-api-cancel-shortcode').on('click', function() {
            $('#school-sports-api-shortcode-popup').hide();
        });
        
        // Close popup when clicking outside
        $(document).on('click', function(e) {
            if ($(e.target).closest('.school-sports-api-shortcode-popup-content').length === 0 && $(e.target).closest('.mce-i-code').length === 0) {
                $('#school-sports-api-shortcode-popup').hide();
            }
        });
    });
    
    /**
     * Update shortcode preview
     */
    function updateShortcodePreview() {
        var shortcodeType = $('#school-sports-api-shortcode-type').val();
        var shortcode = '';
        
        if (shortcodeType === 'live') {
            shortcode = '[school_sports_api_live';
            
            var schoolType = $('#school-sports-api-live-school-type').val();
            if (schoolType) {
                shortcode += ' school_type="' + schoolType + '"';
            }
            
            var refreshInterval = $('#school-sports-api-refresh-interval').val();
            if (refreshInterval) {
                shortcode += ' refresh_interval="' + refreshInterval + '"';
            }
        } else {
            shortcode = '[school_sports_api_results';
            
            var sport = $('#school-sports-api-sport').val();
            if (sport) {
                shortcode += ' sport="' + sport + '"';
            }
            
            var schoolType = $('#school-sports-api-school-type').val();
            if (schoolType) {
                shortcode += ' school_type="' + schoolType + '"';
            }
            
            var schoolYear = $('#school-sports-api-school-year').val();
            if (schoolYear) {
                shortcode += ' school_year="' + schoolYear + '"';
            }
        }
        
        shortcode += ']';
        
        $('#school-sports-api-shortcode-preview').val(shortcode);
    }
    
    /**
     * Settings Page
     */
    $(document).ready(function() {
        // Toggle WebSocket URL field based on WebSocket enabled checkbox
        $('input[name="school_sports_api_options[websocket_enabled]"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('input[name="school_sports_api_options[websocket_url]"]').closest('tr').show();
            } else {
                $('input[name="school_sports_api_options[websocket_url]"]').closest('tr').hide();
            }
        }).trigger('change');
        
        // Copy shortcode button
        $('#admin-copy-shortcode').on('click', function() {
            var shortcode = $('#admin-shortcode-preview').val();
            navigator.clipboard.writeText(shortcode).then(function() {
                alert('Shortcode kopiran u međuspremnik!');
            });
        });

        // Handle Reset Cache button click
        $('#school-sports-api-reset-cache-button').on('click', function() {
            if (confirm(school_sports_api_admin_ajax.reset_confirm_message)) {
                var $button = $(this);
                var $messageDiv = $('#school-sports-api-cache-reset-message');
                
                $button.prop('disabled', true);
                // Ensure base 'notice' class is present, then add specific type and make visible
                $messageDiv.attr('class', 'notice notice-info').text('Resetiranje predmemorije...').css('display', 'block !important');

                $.ajax({
                    url: school_sports_api_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'school_sports_api_reset_cache',
                        nonce: school_sports_api_admin_ajax.nonce
                    },
                    success: function(response) {
                        var messageText = '';
                        if (response.success) {
                            messageText = response.data || school_sports_api_admin_ajax.reset_success_message;
                            $messageDiv.attr('class', 'notice notice-success').text(messageText).css('display', 'block !important');
                        } else {
                            messageText = response.data || school_sports_api_admin_ajax.reset_error_message;
                            $messageDiv.attr('class', 'notice notice-error').text(messageText).css('display', 'block !important');
                        }
                        console.log('Cache reset response:', response); // Log server response
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $messageDiv.attr('class', 'notice notice-error').text(school_sports_api_admin_ajax.reset_error_message).css('display', 'block !important');
                        console.error('Cache reset AJAX error:', textStatus, errorThrown); // Log AJAX error
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        // Hide the message after a few seconds
                        setTimeout(function() {
                            $messageDiv.fadeOut(function() {
                                $(this).css('display', 'none'); // Ensure it's properly hidden for next time
                            });
                        }, 5000);
                    }
                });
            }
        });
    });
})(jQuery);