/**
 * School Sports API Public JavaScript
 *
 * Handles the public-facing functionality, including real-time updates.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

(function($) {
    'use strict';

    /**
     * Sports Results
     */
    var SportsResults = {
        timer: null,
        
        init: function() {
            this.bindEvents();
            this.initTabs();
            // Don't load results immediately, just start the refresh timer
            this.startRefreshTimer();
            
            // Force filter to be visible immediately
            $('.school-sports-api-filter').css({
                'display': 'block',
                'visibility': 'visible'
            });
            
            $('.school-sports-api-filter select').css({
                'display': 'block',
                'visibility': 'visible'
            });
        },

        bindEvents: function() {
            // Filter change event
            $('.school-sports-api-filter select').on('change', this.filterResults);
            
            // Tab click event
            $('.school-sports-api-tab').on('click', this.switchTab);
        },

        initTabs: function() {
            // Show the first tab by default
            $('.school-sports-api-tab-content').hide();
            $('.school-sports-api-tab-content:first').show();
            $('.school-sports-api-tab:first').addClass('active');
        },

        switchTab: function() {
            var tabId = $(this).data('tab');
            
            // Hide all tab contents
            $('.school-sports-api-tab-content').hide();
            
            // Remove active class from all tabs
            $('.school-sports-api-tab').removeClass('active');
            
            // Show the selected tab content
            $('#' + tabId).show();
            
            // Add active class to the clicked tab
            $(this).addClass('active');
        },

        filterResults: function() {
            var filter = $(this).val();
            var $select = $(this);
            
            if (filter === 'all') {
                // Show all groups
                $('.school-sports-api-group').show();
            } else if (filter.startsWith('all-')) {
                // Show all groups for a specific gender
                var gender = filter.replace('all-', '');
                $('.school-sports-api-group').hide();
                $('.school-sports-api-group[data-gender="' + gender + '"]').show();
                
                // Reset filter to "all-gender" when switching genders
                if ($select.data('last-gender') && $select.data('last-gender') !== gender) {
                    $select.val('all-' + gender);
                }
                
                // Store the current gender for next comparison
                $select.data('last-gender', gender);
            } else {
                // Show only the selected group
                $('.school-sports-api-group').hide();
                $('.school-sports-api-group[data-group="' + filter + '"]').show();
            }
        },
        
        loadSportsResults: function() {
            // Get all results containers
            $('.school-sports-api-container').each(function() {
                var $container = $(this);
                
                // Skip live results containers
                if ($container.find('.school-sports-api-live').length > 0) {
                    return;
                }
                
                // Show loading indicator
                var $loading = $('<div class="school-sports-api-loading">' + schoolSportsApiTranslations.loading + '</div>');
                // Don't replace content, just add loading indicator
                $container.find('.school-sports-api-loading').remove(); // Remove any existing loading indicators
                $container.append($loading);
                
                // Get container data attributes
                var sport = $container.data('sport') || 'odbojka';
                var schoolType = $container.data('school-type') || 'ss';
                var schoolYear = $container.data('school-year') || '2024';
                
                // Store current scroll position and active tab
                var scrollPos = $(window).scrollTop();
                var activeTabId = $('.school-sports-api-tab.active').data('tab');
                var activeFilters = {};
                
                // Store current filter selections
                $('.school-sports-api-filter select').each(function() {
                    var $select = $(this);
                    activeFilters[$select.closest('.school-sports-api-tab-content').attr('id')] = $select.val();
                });
                
                // Make AJAX request
                $.ajax({
                    url: school_sports_api.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'fetch_sports_results',
                        nonce: school_sports_api.nonce,
                        sport: sport,
                        school_type: schoolType,
                        school_year: schoolYear
                    },
                    success: function(response) {
                        // Remove loading indicator
                        $loading.remove();
                        
                        // Create a temporary div to parse the response
                        var $temp = $('<div>').html(response);
                        
                        // Replace the content of each tab
                        $temp.find('.school-sports-api-tab-content').each(function() {
                            var $newTabContent = $(this);
                            var tabId = $newTabContent.attr('id');
                            var $oldTabContent = $('#' + tabId);
                            
                            // Only replace if the tab exists
                            if ($oldTabContent.length > 0) {
                                $oldTabContent.html($newTabContent.html());
                            }
                        });
                        
                        // Don't replace the entire content, just update the parts we need
                        if ($temp.find('.school-sports-api-tab-content').length === 0) {
                            // For non-tabbed content, update only the content part, not the entire container
                            var $content = $temp.find('.school-sports-api-content');
                            if ($content.length > 0) {
                                var $existingContent = $container.find('.school-sports-api-content');
                                if ($existingContent.length > 0) {
                                    $existingContent.html($content.html());
                                } else {
                                    // If we can't find a content container, update everything except loading indicators
                                    var currentLoading = $container.find('.school-sports-api-loading');
                                    var currentTimestamp = $container.find('.school-sports-api-live-timestamp');
                                    $container.html($temp.html());
                                    // Re-append loading if it existed
                                    if (currentLoading.length > 0) $container.append(currentLoading);
                                    // Don't re-append timestamp as we'll handle it separately
                                }
                            }
                        } else {
                            // Update tabs
                            $container.find('.school-sports-api-tabs').replaceWith($temp.find('.school-sports-api-tabs'));
                        }
                        
                        // Re-initialize tabs
                        SportsResults.initTabs();
                        
                        // Restore active tab
                        if (activeTabId) {
                            $('.school-sports-api-tab[data-tab="' + activeTabId + '"]').trigger('click');
                        }
                        
                        // Restore filter selections
                        $.each(activeFilters, function(tabId, filterValue) {
                            var $select = $('#' + tabId).find('.school-sports-api-filter select');
                            if ($select.length > 0) {
                                $select.val(filterValue).trigger('change');
                            }
                        });
                        
                        // Rebind events
                        SportsResults.bindEvents();
                        
                        // Check if there are any results before showing timestamp
                        var hasResults = $temp.find('.school-sports-api-group').length > 0 ||
                                        $temp.find('.school-sports-api-tab-content').length > 0 ||
                                        $temp.find('table').length > 0 ||
                                        $container.find('.school-sports-api-group').length > 0 ||
                                        $container.find('table').length > 0;
                        
                        // Always remove any existing timestamps first to prevent duplication
                        $container.find('.school-sports-api-live-timestamp').remove();
                        
                        // Update timestamp only if there are results
                        if (hasResults) {
                            var now = new Date();
                            var timestamp = now.toLocaleTimeString();
                            $container.append('<div class="school-sports-api-live-timestamp">' + schoolSportsApiTranslations.lastUpdated + ': ' + timestamp + '</div>');
                        }
                        
                        // Restore scroll position
                        $(window).scrollTop(scrollPos);
                    },
                    error: function(xhr, status, error) {
                        // Remove loading indicator
                        $loading.remove();
                        
                        // Just log the error, don't display it to the user
                        console.error('Error refreshing results:', status, error);
                    }
                });
            });
        },
        
        startRefreshTimer: function() {
            // Set refresh interval to 1 minute (60000 ms)
            var refreshInterval = parseInt(school_sports_api.refresh_interval) * 1000 || 60000;
            
            // Check for custom refresh interval
            if (typeof school_sports_api_custom_refresh !== 'undefined') {
                refreshInterval = parseInt(school_sports_api_custom_refresh) * 1000;
            }
            
            // Clear any existing timer
            if (this.timer) {
                clearInterval(this.timer);
            }
            
            // Set new timer
            this.timer = setInterval(this.loadSportsResults, refreshInterval);
        },
        
        // The refreshResults method has been replaced by loadSportsResults
    };

    /**
     * Live Results
     */
    var LiveResults = {
        timer: null,
        
        init: function() {
            console.log('LiveResults.init() called');
            // Don't load results immediately, just start the refresh timer
            this.startRefreshTimer();
            
            // Force filter to be visible immediately
            $('.school-sports-api-filter').css({
                'display': 'block',
                'visibility': 'visible'
            });
            
            $('.school-sports-api-filter select').css({
                'display': 'block',
                'visibility': 'visible'
            });
        },

        loadLiveResults: function() {
            console.log('Loading live results...');
            var $container = $('.school-sports-api-live');
            
            console.log('Live container found:', $container.length);
            if ($container.length === 0) {
                console.log('No live container found, exiting');
                return;
            }
            
            // Show loading indicator
            var $loading = $('<div class="school-sports-api-loading">' + schoolSportsApiTranslations.loading + '</div>');
            // Don't replace content, just add loading indicator
            $container.find('.school-sports-api-loading').remove(); // Remove any existing loading indicators
            $container.append($loading);
            
            // Get school type if specified
            var schoolType = $container.data('school-type') || '';
            
            // Make AJAX request
            $.ajax({
                url: school_sports_api.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_live_results',
                    nonce: school_sports_api.nonce,
                    school_type: schoolType
                },
                success: function(response) {
                    // Remove loading indicator
                    $loading.remove();
                    
                    console.log('AJAX success, response length:', response.length);
                    
                    // Log the first 200 characters of the response for debugging
                    console.log('Response preview:', response.substring(0, 200));
                    
                    // Create a temporary div to parse the response
                    var $temp = $('<div>').html(response);
                    
                    // Store current content that we want to preserve
                    var $currentLoading = $container.find('.school-sports-api-loading');
                    var $currentTimestamp = $container.find('.school-sports-api-live-timestamp');
                    
                    // Update container with response but preserve loading indicator and timestamp
                    $container.html(response);
                    
                    // Re-append loading if it existed
                    if ($currentLoading.length > 0) $container.append($currentLoading);
                    // Don't re-append timestamp as we'll handle it separately
                    
                    console.log('Container updated with response');
                    
                    // Initialize filter if present
                    var $filter = $('.school-sports-api-filter select');
                    console.log('Filter elements found:', $filter.length);
                    
                    if ($filter.length > 0) {
                        console.log('Found filter dropdown, initializing...');
                        $filter.off('change').on('change', LiveResults.filterResults);
                        
                        // Force filter to be visible
                        $('.school-sports-api-filter').css({
                            'display': 'block',
                            'visibility': 'visible'
                        });
                        
                        $filter.css({
                            'display': 'block',
                            'visibility': 'visible'
                        });
                        
                        // Log available groups for debugging
                        console.log('Available groups:', $('.school-sports-api-group').length);
                        $('.school-sports-api-group').each(function() {
                            console.log('Group:', $(this).attr('data-group'), 'Gender:', $(this).attr('data-gender'));
                        });
                    } else {
                        console.log('No filter dropdown found');
                    }
                    
                    // Check if there are any results before showing timestamp
                    var hasResults = $('.school-sports-api-group').length > 0 ||
                                    $('table', $container).length > 0 ||
                                    $temp.find('.school-sports-api-group').length > 0 ||
                                    $temp.find('table').length > 0;
                    
                    // Always remove any existing timestamps first to prevent duplication
                    $container.find('.school-sports-api-live-timestamp').remove();
                    
                    // Add timestamp only if there are results
                    if (hasResults) {
                        var now = new Date();
                        var timestamp = now.toLocaleTimeString();
                        $container.append('<div class="school-sports-api-live-timestamp">' + schoolSportsApiTranslations.lastUpdated + ': ' + timestamp + '</div>');
                        
                        // Show the header if there are results
                        $('.school-sports-api-live-header').show();
                    } else {
                        // Hide the header if there are no results
                        $('.school-sports-api-live-header').hide();
                    }
                },
                error: function() {
                    // Remove loading indicator
                    $loading.remove();
                    
                    // Show error message
                    $container.append('<div class="school-sports-api-error">' + schoolSportsApiTranslations.error + '</div>');
                }
            });
        },

        startRefreshTimer: function() {
            var refreshInterval = parseInt(school_sports_api.refresh_interval) * 1000;
            
            // Check for custom refresh interval
            if (typeof school_sports_api_custom_refresh !== 'undefined') {
                refreshInterval = parseInt(school_sports_api_custom_refresh) * 1000;
            }
            
            // Clear any existing timer
            if (this.timer) {
                clearInterval(this.timer);
            }
            
            // Set new timer
            this.timer = setInterval(this.loadLiveResults, refreshInterval);
        },

        filterResults: function() {
            var filter = $(this).val();
            var $select = $(this);
            console.log('Filtering by:', filter); // Debug log
            
            if (filter === 'all') {
                // Show all groups
                $('.school-sports-api-group').show();
                console.log('Showing all groups:', $('.school-sports-api-group').length);
            } else if (filter.startsWith('all-')) {
                // Show all groups for a specific gender
                var gender = filter.replace('all-', '');
                console.log('Filtering by gender:', gender);
                console.log('Groups before hiding:', $('.school-sports-api-group').length);
                $('.school-sports-api-group').hide();
                console.log('Groups with this gender:', $('.school-sports-api-group[data-gender="' + gender + '"]').length);
                $('.school-sports-api-group[data-gender="' + gender + '"]').show();
                
                // Reset filter to "all-gender" when switching genders
                if ($select.data('last-gender') && $select.data('last-gender') !== gender) {
                    $select.val('all-' + gender);
                }
                
                // Store the current gender for next comparison
                $select.data('last-gender', gender);
            } else {
                // Show only the selected group
                console.log('Filtering by group:', filter);
                console.log('Groups before hiding:', $('.school-sports-api-group').length);
                $('.school-sports-api-group').hide();
                console.log('Groups with this ID:', $('.school-sports-api-group[data-group="' + filter + '"]').length);
                $('.school-sports-api-group[data-group="' + filter + '"]').show();
            }
        }
    };

    /**
     * WebSocket Connection
     */
    var WebSocketConnection = {
        socket: null,
        
        init: function() {
            // Check if WebSocket is supported
            if ('WebSocket' in window) {
                this.connect();
            }
        },

        connect: function() {
            var wsUrl = school_sports_api.ws_url;
            
            if (!wsUrl) {
                return;
            }
            
            // Create WebSocket connection
            this.socket = new WebSocket(wsUrl);
            
            // Connection opened
            this.socket.addEventListener('open', function(event) {
                console.log('WebSocket veza uspostavljena');
            });
            
            // Listen for messages
            this.socket.addEventListener('message', function(event) {
                var data = JSON.parse(event.data);
                
                // Handle different message types
                if (data.type === 'update') {
                    LiveResults.loadLiveResults();
                }
            });
            
            // Connection closed
            this.socket.addEventListener('close', function(event) {
                console.log('WebSocket veza zatvorena');
                
                // Try to reconnect after a delay
                setTimeout(function() {
                    WebSocketConnection.connect();
                }, 5000);
            });
            
            // Connection error
            this.socket.addEventListener('error', function(event) {
                console.error('WebSocket greška:', event);
            });
        },

        disconnect: function() {
            if (this.socket) {
                this.socket.close();
                this.socket = null;
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Add data attributes to results containers for AJAX refresh
        $('.school-sports-api-container').each(function() {
            var $container = $(this);
            
            // Skip live results containers
            if ($container.find('.school-sports-api-live').length > 0) {
                return;
            }
            
            // Extract shortcode attributes from container classes or data attributes
            var sportMatch = $container.attr('class').match(/sport-([a-z0-9]+)/);
            var schoolTypeMatch = $container.attr('class').match(/school-type-([a-z]+)/);
            var schoolYearMatch = $container.attr('class').match(/school-year-([0-9]+)/);
            
            // Set data attributes for AJAX refresh
            if (sportMatch) {
                $container.attr('data-sport', sportMatch[1]);
            }
            
            if (schoolTypeMatch) {
                $container.attr('data-school-type', schoolTypeMatch[1]);
            }
            
            if (schoolYearMatch) {
                $container.attr('data-school-year', schoolYearMatch[1]);
            }
        });
        
        // Initialize sports results if present
        if ($('.school-sports-api-container').length > 0 && $('.school-sports-api-live').length === 0) {
            SportsResults.init();
            
            // Force filter to be visible after a short delay
            setTimeout(function() {
                $('.school-sports-api-filter').css({
                    'display': 'block',
                    'visibility': 'visible'
                });
                
                $('.school-sports-api-filter select').css({
                    'display': 'block',
                    'visibility': 'visible'
                });
                
                // Re-bind event handler
                $('.school-sports-api-filter select').off('change').on('change', SportsResults.filterResults);
            }, 1000);
        }
        
        // Initialize live results if present
        if ($('.school-sports-api-live').length > 0) {
            console.log('Live results container found, initializing...');
            LiveResults.init();
            
            // Force filter to be visible after a short delay
            setTimeout(function() {
                console.log('Forcing filter visibility...');
                $('.school-sports-api-filter').css({
                    'display': 'block',
                    'visibility': 'visible'
                });
                
                $('.school-sports-api-filter select').css({
                    'display': 'block',
                    'visibility': 'visible'
                });
                
                // Re-bind event handler
                $('.school-sports-api-filter select').off('change').on('change', LiveResults.filterResults);
            }, 1000);
            
            // Initialize WebSocket connection if available
            if (typeof school_sports_api.ws_url !== 'undefined') {
                WebSocketConnection.init();
            }
        }
        
        // Add timestamp to regular results
        if ($('.school-sports-api-container').length > 0 && $('.school-sports-api-live').length === 0) {
            var now = new Date();
            var timestamp = now.toLocaleTimeString();
            $('.school-sports-api-container').append('<div class="school-sports-api-live-timestamp">' + schoolSportsApiTranslations.lastUpdated + ': ' + timestamp + '</div>');
        }
    });

    // Translations object
    var schoolSportsApiTranslations = {
        loading: 'Učitavanje...',
        lastUpdated: 'Zadnje ažurirano',
        error: 'Greška pri učitavanju podataka. Molimo pokušajte ponovno.'
    };

})(jQuery);