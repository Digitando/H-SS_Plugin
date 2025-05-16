<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="school-sports-api-admin-header">
        <div class="school-sports-api-admin-about">
            <h2><?php esc_html_e('O School Sports API', 'school-sports-api'); ?></h2>
            <p><?php esc_html_e('Ovaj dodatak integrira se sa School Sports API-jem za prikaz sportskih podataka, rezultata i rasporeda na vašoj WordPress stranici.', 'school-sports-api'); ?></p>
            <p><?php esc_html_e('Koristite postavke u nastavku za konfiguriranje API veze i opcija predmemorije.', 'school-sports-api'); ?></p>
        </div>
    </div>

    <div class="school-sports-api-admin-main">
        <form method="post" action="options.php">
            <?php
            settings_fields('school_sports_api_options');
            do_settings_sections($this->plugin_name);
            submit_button();
            ?>
        </form>
    </div>

    <div class="school-sports-api-admin-sidebar">
        <div class="school-sports-api-admin-box">
            <h3><?php esc_html_e('Dostupni Shortcodeovi', 'school-sports-api'); ?></h3>
            <p><?php esc_html_e('Koristite ove shortcodeove za prikaz sportskih podataka na vašoj stranici:', 'school-sports-api'); ?></p>
            
            <h4><?php esc_html_e('Shortcode za Sportske Rezultate', 'school-sports-api'); ?></h4>
            <p><code>[school_sports_api_results]</code></p>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Parametar', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Opis', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Zadano', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Opcije', 'school-sports-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>sport</code></td>
                        <td><?php esc_html_e('Sport za koji se prikazuju rezultati', 'school-sports-api'); ?></td>
                        <td><code>odbojka</code></td>
                        <td>
                            <ul>
                                <li><code>odbojka</code> - <?php esc_html_e('Odbojka', 'school-sports-api'); ?></li>
                                <li><code>futsal</code> - <?php esc_html_e('Futsal', 'school-sports-api'); ?></li>
                                <li><code>kosarka</code> - <?php esc_html_e('Košarka', 'school-sports-api'); ?></li>
                                <li><code>kosarka3x3</code> - <?php esc_html_e('Košarka 3x3', 'school-sports-api'); ?></li>
                                <li><code>rukomet</code> - <?php esc_html_e('Rukomet', 'school-sports-api'); ?></li>
                                <li><code>badminton</code> - <?php esc_html_e('Badminton', 'school-sports-api'); ?></li>
                                <li><code>stolnitenis</code> - <?php esc_html_e('Stolni tenis', 'school-sports-api'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><code>school_type</code></td>
                        <td><?php esc_html_e('Tip škole za koju se prikazuju rezultati', 'school-sports-api'); ?></td>
                        <td><code>ss</code></td>
                        <td>
                            <ul>
                                <li><code>ss</code> - <?php esc_html_e('Srednja škola (prikazuje Mladiće i Djevojke)', 'school-sports-api'); ?></li>
                                <li><code>os</code> - <?php esc_html_e('Osnovna škola (prikazuje Dječake i Djevojčice)', 'school-sports-api'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><code>school_year</code></td>
                        <td><?php esc_html_e('Školska godina za koju se prikazuju rezultati', 'school-sports-api'); ?></td>
                        <td><code>2024</code></td>
                        <td>
                            <ul>
                                <li><code>2029</code> - <?php esc_html_e('Školska godina 2028/2029', 'school-sports-api'); ?></li>
                                <li><code>2028</code> - <?php esc_html_e('Školska godina 2027/2028', 'school-sports-api'); ?></li>
                                <li><code>2027</code> - <?php esc_html_e('Školska godina 2026/2027', 'school-sports-api'); ?></li>
                                <li><code>2026</code> - <?php esc_html_e('Školska godina 2025/2026', 'school-sports-api'); ?></li>
                                <li><code>2025</code> - <?php esc_html_e('Školska godina 2024/2025', 'school-sports-api'); ?></li>
                                <li><code>2024</code> - <?php esc_html_e('Školska godina 2023/2024', 'school-sports-api'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><code>testing</code></td>
                        <td><?php esc_html_e('Koristi testni API URL (vrijednost mora biti "test")', 'school-sports-api'); ?></td>
                        <td><code>test</code> </td>
                        <td><code>test</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h4><?php esc_html_e('Shortcode za Rezultate Uživo', 'school-sports-api'); ?></h4>
            <p><code>[school_sports_api_live]</code></p>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Parametar', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Opis', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Zadano', 'school-sports-api'); ?></th>
                        <th><?php esc_html_e('Opcije', 'school-sports-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>school_type</code></td>
                        <td><?php esc_html_e('Tip škole za koju se prikazuju rezultati uživo', 'school-sports-api'); ?></td>
                        <td><?php esc_html_e('(prazno - prikazuje sve)', 'school-sports-api'); ?></td>
                        <td>
                            <ul>
                                <li><code>ss</code> - <?php esc_html_e('Srednja škola (prikazuje Mladiće i Djevojke)', 'school-sports-api'); ?></li>
                                <li><code>os</code> - <?php esc_html_e('Osnovna škola (prikazuje Dječake i Djevojčice)', 'school-sports-api'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><code>refresh_interval</code></td>
                        <td><?php esc_html_e('Interval u sekundama za osvježavanje rezultata uživo', 'school-sports-api'); ?></td>
                        <td><?php esc_html_e('(koristi globalnu postavku)', 'school-sports-api'); ?></td>
                        <td><?php esc_html_e('Bilo koji pozitivan broj (npr. 30, 60, 120)', 'school-sports-api'); ?></td>
                    </tr>
                     <tr>
                        <td><code>testing</code></td>
                        <td><?php esc_html_e('Koristi testni API URL (vrijednost mora biti "test")', 'school-sports-api'); ?></td>
                        <td>(prazno)</td>
                        <td><code>test</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h4><?php esc_html_e('Primjeri Shortcodeova', 'school-sports-api'); ?></h4>
            <ul>
                <li><code>[school_sports_api_results sport="odbojka" school_type="ss" school_year="2024"]</code> - <?php esc_html_e('Prikazuje rezultate odbojke za srednje škole za 2023/2024', 'school-sports-api'); ?></li>
                <li><code>[school_sports_api_results sport="futsal" school_type="os" school_year="2027"]</code> - <?php esc_html_e('Prikazuje rezultate futsala za osnovne škole za 2026/2027', 'school-sports-api'); ?></li>
                <li><code>[school_sports_api_results sport="kosarka" school_type="ss" school_year="2025"]</code> - <?php esc_html_e('Prikazuje rezultate košarke za srednje škole za 2024/2025', 'school-sports-api'); ?></li>
                <li><code>[school_sports_api_live school_type="ss"]</code> - <?php esc_html_e('Prikazuje rezultate uživo samo za srednje škole', 'school-sports-api'); ?></li>
                <li><code>[school_sports_api_live refresh_interval="30"]</code> - <?php esc_html_e('Prikazuje sve rezultate uživo s intervalom osvježavanja od 30 sekundi', 'school-sports-api'); ?></li>
            </ul>
            
            <p><?php esc_html_e('Također možete koristiti gumb za generiranje shortcodea u uređivaču za vizualno stvaranje shortcodeova.', 'school-sports-api'); ?></p>
        </div>

        <div class="school-sports-api-admin-box">
            <h3><?php esc_html_e('Generator Shortcodeova', 'school-sports-api'); ?></h3>
            <p><?php esc_html_e('Koristite ovaj generator za stvaranje shortcodeova za vašu stranicu:', 'school-sports-api'); ?></p>
            
            <div class="school-sports-api-shortcode-generator">
                <div class="school-sports-api-shortcode-field">
                    <label for="admin-shortcode-type"><?php esc_html_e('Tip Shortcodea', 'school-sports-api'); ?></label>
                    <select id="admin-shortcode-type">
                        <option value="results"><?php esc_html_e('Sportski Rezultati', 'school-sports-api'); ?></option>
                        <option value="live"><?php esc_html_e('Rezultati Uživo', 'school-sports-api'); ?></option>
                    </select>
                </div>
                
                <div id="admin-results-options">
                    <div class="school-sports-api-shortcode-field">
                        <label for="admin-sport"><?php esc_html_e('Sport', 'school-sports-api'); ?></label>
                        <select id="admin-sport">
                            <option value="odbojka"><?php esc_html_e('Odbojka', 'school-sports-api'); ?></option>
                            <option value="futsal"><?php esc_html_e('Futsal', 'school-sports-api'); ?></option>
                            <option value="kosarka"><?php esc_html_e('Košarka', 'school-sports-api'); ?></option>
                            <option value="kosarka3x3"><?php esc_html_e('Košarka 3x3', 'school-sports-api'); ?></option>
                            <option value="rukomet"><?php esc_html_e('Rukomet', 'school-sports-api'); ?></option>
                            <option value="badminton"><?php esc_html_e('Badminton', 'school-sports-api'); ?></option>
                            <option value="stolnitenis"><?php esc_html_e('Stolni tenis', 'school-sports-api'); ?></option>
                        </select>
                    </div>
                    
                    <div class="school-sports-api-shortcode-field">
                        <label for="admin-school-type"><?php esc_html_e('Tip Škole', 'school-sports-api'); ?></label>
                        <select id="admin-school-type">
                            <option value="ss"><?php esc_html_e('Srednja Škola', 'school-sports-api'); ?></option>
                            <option value="os"><?php esc_html_e('Osnovna Škola', 'school-sports-api'); ?></option>
                        </select>
                    </div>
                    
                    <div class="school-sports-api-shortcode-field">
                        <label for="admin-school-year"><?php esc_html_e('Školska Godina', 'school-sports-api'); ?></label>
                        <select id="admin-school-year">
                            <option value="2029"><?php esc_html_e('2028/2029', 'school-sports-api'); ?></option>
                            <option value="2028"><?php esc_html_e('2027/2028', 'school-sports-api'); ?></option>
                            <option value="2027"><?php esc_html_e('2026/2027', 'school-sports-api'); ?></option>
                            <option value="2026"><?php esc_html_e('2025/2026', 'school-sports-api'); ?></option>
                            <option value="2025"><?php esc_html_e('2024/2025', 'school-sports-api'); ?></option>
                            <option value="2024" selected><?php esc_html_e('2023/2024', 'school-sports-api'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div id="admin-live-options" style="display:none;">
                    <div class="school-sports-api-shortcode-field">
                        <label for="admin-live-school-type"><?php esc_html_e('Tip Škole', 'school-sports-api'); ?></label>
                        <select id="admin-live-school-type">
                            <option value=""><?php esc_html_e('Sve Škole', 'school-sports-api'); ?></option>
                            <option value="ss"><?php esc_html_e('Srednja Škola', 'school-sports-api'); ?></option>
                            <option value="os"><?php esc_html_e('Osnovna Škola', 'school-sports-api'); ?></option>
                        </select>
                    </div>
                    
                    <div class="school-sports-api-shortcode-field">
                        <label for="admin-refresh-interval"><?php esc_html_e('Interval Osvježavanja (sekunde)', 'school-sports-api'); ?></label>
                        <input type="number" id="admin-refresh-interval" min="30" step="1" placeholder="<?php esc_attr_e('Zadano', 'school-sports-api'); ?>">
                    </div>
                </div>

                <div class="school-sports-api-shortcode-field">
                    <label for="admin-testing-mode"><?php esc_html_e('Testni Način', 'school-sports-api'); ?></label>
                    <input type="checkbox" id="admin-testing-mode">
                    <p class="description"><?php esc_html_e('Ako je označeno, koristit će testni API URL.', 'school-sports-api'); ?></p>
                </div>
                
                <div class="school-sports-api-shortcode-preview">
                    <label><?php esc_html_e('Pregled Shortcodea', 'school-sports-api'); ?></label>
                    <input type="text" id="admin-shortcode-preview" readonly>
                </div>
                
                <div class="school-sports-api-shortcode-actions">
                    <button type="button" class="button button-primary" id="admin-copy-shortcode"><?php esc_html_e('Kopiraj Shortcode', 'school-sports-api'); ?></button>
                </div>
            </div>
            
            <script>
                jQuery(document).ready(function($) {
                    // Update shortcode preview on change
                    $('#admin-shortcode-type, #admin-sport, #admin-school-type, #admin-school-year, #admin-live-school-type, #admin-refresh-interval, #admin-testing-mode').on('change input', updateShortcodePreview);
                    
                    // Toggle options based on shortcode type
                    $('#admin-shortcode-type').on('change', function() {
                        if ($(this).val() === 'live') {
                            $('#admin-results-options').hide();
                            $('#admin-live-options').show();
                        } else {
                            $('#admin-results-options').show();
                            $('#admin-live-options').hide();
                        }
                        updateShortcodePreview();
                    });
                    
                    // Copy shortcode to clipboard
                    $('#admin-copy-shortcode').on('click', function() {
                        var shortcode = $('#admin-shortcode-preview').val();
                        navigator.clipboard.writeText(shortcode).then(function() {
                            alert('<?php echo esc_js(__('Shortcode kopiran u međuspremnik!', 'school-sports-api')); ?>');
                        });
                    });
                    
                    // Initial update
                    updateShortcodePreview();
                    
                    function updateShortcodePreview() {
                        var shortcodeType = $('#admin-shortcode-type').val();
                        var shortcode = '';
                        
                        if (shortcodeType === 'live') {
                            shortcode = '[school_sports_api_live';
                            
                            var schoolType = $('#admin-live-school-type').val();
                            if (schoolType) {
                                shortcode += ' school_type="' + schoolType + '"';
                            }
                            
                            var refreshInterval = $('#admin-refresh-interval').val();
                            if (refreshInterval) {
                                shortcode += ' refresh_interval="' + refreshInterval + '"';
                            }
                        } else {
                            shortcode = '[school_sports_api_results';
                            
                            var sport = $('#admin-sport').val();
                            if (sport) {
                                shortcode += ' sport="' + sport + '"';
                            }
                            
                            var schoolType = $('#admin-school-type').val();
                            if (schoolType) {
                                shortcode += ' school_type="' + schoolType + '"';
                            }
                            
                            var schoolYear = $('#admin-school-year').val();
                            if (schoolYear) {
                                shortcode += ' school_year="' + schoolYear + '"';
                            }
                        }
                        
                        var testingMode = $('#admin-testing-mode').is(':checked');
                        if (testingMode) {
                            shortcode += ' testing="test"';
                        }

                        shortcode += ']';
                        
                        $('#admin-shortcode-preview').val(shortcode);
                    }
                });
            </script>
            
            <style>
                .school-sports-api-shortcode-generator {
                    background: #f9f9f9;
                    border: 1px solid #e5e5e5;
                    padding: 15px;
                    border-radius: 3px;
                    margin-top: 15px;
                }
                
                .school-sports-api-shortcode-field {
                    margin-bottom: 15px;
                }
                
                .school-sports-api-shortcode-field label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                
                .school-sports-api-shortcode-field select,
                .school-sports-api-shortcode-field input,
                .school-sports-api-shortcode-preview input {
                    width: 100%;
                }
                
                .school-sports-api-shortcode-preview {
                    margin-top: 20px;
                    margin-bottom: 15px;
                }
                
                .school-sports-api-shortcode-actions {
                    text-align: right;
                }
            </style>
        </div>

        <div class="school-sports-api-admin-box">
            <h3><?php esc_html_e('Trebate Pomoć?', 'school-sports-api'); ?></h3>
            <p><?php esc_html_e('Za podršku ili zahtjeve za nove funkcionalnosti, molimo kontaktirajte autora dodatka.', 'school-sports-api'); ?></p>
        </div>
    </div>

    <div class="school-sports-api-admin-footer">
        <p><?php esc_html_e('School Sports API Dodatak', 'school-sports-api'); ?> | <?php esc_html_e('Verzija', 'school-sports-api'); ?> <?php echo esc_html(SCHOOL_SPORTS_API_VERSION); ?></p>
    </div>
</div>