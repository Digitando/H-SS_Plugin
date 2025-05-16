<?php
/**
 * Provide a shortcode generator popup for the editor
 *
 * This file is used to markup the shortcode generator popup.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div id="school-sports-api-shortcode-popup" style="display:none;">
    <div class="school-sports-api-shortcode-popup-content">
        <h2><?php esc_html_e('School Sports API Generator Shortcodea', 'school-sports-api'); ?></h2>
        
        <div class="school-sports-api-shortcode-form">
            <div class="school-sports-api-shortcode-field">
                <label for="school-sports-api-shortcode-type"><?php esc_html_e('Tip Shortcodea', 'school-sports-api'); ?></label>
                <select id="school-sports-api-shortcode-type">
                    <option value="results"><?php esc_html_e('Sportski Rezultati', 'school-sports-api'); ?></option>
                    <option value="live"><?php esc_html_e('Rezultati Uživo', 'school-sports-api'); ?></option>
                </select>
            </div>
            
            <div id="school-sports-api-results-options">
                <div class="school-sports-api-shortcode-field">
                    <label for="school-sports-api-sport"><?php esc_html_e('Sport', 'school-sports-api'); ?></label>
                    <select id="school-sports-api-sport">
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
                    <label for="school-sports-api-school-type"><?php esc_html_e('Tip Škole', 'school-sports-api'); ?></label>
                    <select id="school-sports-api-school-type">
                        <option value="ss"><?php esc_html_e('Srednja Škola', 'school-sports-api'); ?></option>
                        <option value="os"><?php esc_html_e('Osnovna Škola', 'school-sports-api'); ?></option>
                    </select>
                </div>
                
                <div class="school-sports-api-shortcode-field">
                    <label for="school-sports-api-school-year"><?php esc_html_e('Školska Godina', 'school-sports-api'); ?></label>
                    <select id="school-sports-api-school-year">
                        <option value="2029"><?php esc_html_e('2028/2029', 'school-sports-api'); ?></option>
                        <option value="2028"><?php esc_html_e('2027/2028', 'school-sports-api'); ?></option>
                        <option value="2027"><?php esc_html_e('2026/2027', 'school-sports-api'); ?></option>
                        <option value="2026"><?php esc_html_e('2025/2026', 'school-sports-api'); ?></option>
                        <option value="2025"><?php esc_html_e('2024/2025', 'school-sports-api'); ?></option>
                        <option value="2024" selected><?php esc_html_e('2023/2024', 'school-sports-api'); ?></option>
                    </select>
                </div>
            </div>
            
            <div id="school-sports-api-live-options" style="display:none;">
                <div class="school-sports-api-shortcode-field">
                    <label for="school-sports-api-live-school-type"><?php esc_html_e('Tip Škole', 'school-sports-api'); ?></label>
                    <select id="school-sports-api-live-school-type">
                        <option value=""><?php esc_html_e('Sve Škole', 'school-sports-api'); ?></option>
                        <option value="ss"><?php esc_html_e('Srednja Škola', 'school-sports-api'); ?></option>
                        <option value="os"><?php esc_html_e('Osnovna Škola', 'school-sports-api'); ?></option>
                    </select>
                </div>
                
                <div class="school-sports-api-shortcode-field">
                    <label for="school-sports-api-refresh-interval"><?php esc_html_e('Interval Osvježavanja (sekunde)', 'school-sports-api'); ?></label>
                    <input type="number" id="school-sports-api-refresh-interval" min="30" step="1" placeholder="<?php esc_attr_e('Zadano', 'school-sports-api'); ?>">
                </div>
            </div>

            <div class="school-sports-api-shortcode-field">
                <label for="school-sports-api-testing-mode"><?php esc_html_e('Testni Način', 'school-sports-api'); ?></label>
                <input type="checkbox" id="school-sports-api-testing-mode">
                <small><?php esc_html_e('Koristi testni API URL.', 'school-sports-api'); ?></small>
            </div>
            
            <div class="school-sports-api-shortcode-preview">
                <label><?php esc_html_e('Pregled Shortcodea', 'school-sports-api'); ?></label>
                <input type="text" id="school-sports-api-shortcode-preview" readonly />
            </div>
            
            <div class="school-sports-api-shortcode-actions">
                <button type="button" class="button button-primary" id="school-sports-api-insert-shortcode"><?php esc_html_e('Umetni Shortcode', 'school-sports-api'); ?></button>
                <button type="button" class="button" id="school-sports-api-cancel-shortcode"><?php esc_html_e('Odustani', 'school-sports-api'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
    #school-sports-api-shortcode-popup {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .school-sports-api-shortcode-popup-content {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        max-width: 500px;
        width: 100%;
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
    .school-sports-api-shortcode-preview input {
        width: 100%;
    }
    
    .school-sports-api-shortcode-actions {
        margin-top: 20px;
        text-align: right;
    }
    
    .school-sports-api-shortcode-actions button {
        margin-left: 10px;
    }
</style>