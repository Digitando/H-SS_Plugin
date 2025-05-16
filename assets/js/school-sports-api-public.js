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
            console.log('[SportsResults.init] Initializing SportsResults...');
            this.bindEvents();

            // Initialize tabs for each container, attempting to restore from localStorage
            var self = this; // Ensure 'this' refers to SportsResults in the .each() callback
            $('.school-sports-api-container').not(':has(.school-sports-api-live)').each(function() {
                var $container = $(this);
                var containerKeySuffix = self.getContainerStorageKeySuffix($container);
                var storedTabId = localStorage.getItem('ssa_active_tab_' + containerKeySuffix);
                if (storedTabId) {
                    console.log('[SportsResults.init] Found stored active tab for container (' + containerKeySuffix + '):', storedTabId);
                } else {
                    // console.log('[SportsResults.init] No stored active tab found for container (' + containerKeySuffix + '). Defaulting to first.');
                }
                self.initTabs($container, storedTabId); // Initialize tabs for this specific container
            });
            
            // Filter visibility is now controlled by CSS defaults.
            // JS will handle dynamic changes if needed (e.g., initial hide then show).
            // For now, we assume CSS default is sufficient for initial display.
            
            // Restore filter values from localStorage and apply them
            this.restoreFilters();
            // Restore scroll positions on initial load for all containers
            $('.school-sports-api-container').not(':has(.school-sports-api-live)').each(function() {
                SportsResults.restoreAllTableScrollPositionsInContainer(this);
            });
            
            // Start the refresh timer (which will also load results immediately)
            this.startRefreshTimer();
            
            console.log('SportsResults initialized');
        },

        bindEvents: function() {
            var self = this;
            // Filter change event
            // Use event delegation for dynamically added filters
            $(document).on('change', '.school-sports-api-container:not(:has(.school-sports-api-live)) .school-sports-api-filter select', this.filterResults.bind(this));
            
            // Tab click event
            // Use event delegation for dynamically added tabs
            $(document).on('click', '.school-sports-api-container:not(:has(.school-sports-api-live)) .school-sports-api-tab', this.switchTab.bind(this));

            // Table scroll event - delegated to a static parent if possible, or document
            // We target .school-sports-api-group within non-live containers
            $(document).on('scroll', '.school-sports-api-container:not(:has(.school-sports-api-live)) .school-sports-api-group', function(event) {
                self.saveTableScrollPosition.call(self, event);
            });
        },

        initTabs: function($containerContext, activeTabIdToRestore) {
            var $containersToProcess = $containerContext ? $containerContext : $('.school-sports-api-container').not(':has(.school-sports-api-live)');
            // console.log('[SportsResults.initTabs] Initializing tabs. Attempting to restore:', activeTabIdToRestore);

            $containersToProcess.each(function() {
                var $container = $(this);
                $container.find('.school-sports-api-tab').removeClass('active');
                $container.find('.school-sports-api-tab-content').hide();

                var $tabToActivate = null;
                var $tabContentToActivate = null;

                if (activeTabIdToRestore) {
                    $tabToActivate = $container.find('.school-sports-api-tab[data-tab="' + activeTabIdToRestore + '"]');
                    $tabContentToActivate = $container.find('#' + activeTabIdToRestore);
                }

                if (!activeTabIdToRestore || !$tabToActivate || !$tabToActivate.length || !$tabContentToActivate || !$tabContentToActivate.length) {
                    $tabToActivate = $container.find('.school-sports-api-tab:first');
                    $tabContentToActivate = $container.find('.school-sports-api-tab-content:first');
                }

                if ($tabToActivate && $tabToActivate.length && $tabContentToActivate && $tabContentToActivate.length) {
                    $tabToActivate.addClass('active');
                    $tabContentToActivate.show();
                }
            });
        },

        switchTab: function(event) {
            var $clickedTab = $(event.currentTarget);
            var $container = $clickedTab.closest('.school-sports-api-container');
            var tabId = $clickedTab.data('tab'); // This is the ID of the target tab content
            
            // Save the active tab to localStorage for this specific container
            if ($container.length && tabId) {
                var containerKeySuffix = this.getContainerStorageKeySuffix($container);
                var localStorageKey = 'ssa_active_tab_' + containerKeySuffix;
                localStorage.setItem(localStorageKey, tabId);
                console.log('[SportsResults.switchTab] Saved active tab to localStorage for container (' + containerKeySuffix + '):', tabId);
            }

            // console.log('[SportsResults.switchTab] Switching to tab. Container ID (if any):', $container.attr('id'), 'Target Tab Content ID:', tabId);

            $container.find('.school-sports-api-tab').removeClass('active');
            $clickedTab.addClass('active');

            $container.find('.school-sports-api-tab-content').hide();
            var $activeTabContent = $container.find('#' + tabId);
            $activeTabContent.show();

            SportsResults.restoreAllTableScrollPositionsInContainer($activeTabContent);

            var $select = $activeTabContent.find('.school-sports-api-filter select');
            if ($select.length > 0) {
                var storedValue = localStorage.getItem('sports_filter_' + tabId);
                var finalValueToSet = null;

                if (storedValue && $select.find('option[value="' + storedValue + '"]').length > 0) {
                    finalValueToSet = storedValue;
                } else {
                    if ($select.find('option[value="all"]').length > 0) {
                        finalValueToSet = 'all';
                    } else if ($select.find('option').length > 0) {
                        finalValueToSet = $select.find('option:first').val();
                    }
                }

                if (finalValueToSet !== null) {
                    $select.val(finalValueToSet);
                    if (storedValue !== finalValueToSet) { // Update localStorage if we defaulted or corrected
                        localStorage.setItem('sports_filter_' + tabId, finalValueToSet);
                    }
                    // console.log('[SportsResults.switchTab] For tab', tabId, 'set filter to:', finalValueToSet, 'and triggering change.');
                    $select.trigger('change'); // Crucial: apply the filter
                }
            } else {
                // console.log('[SportsResults.switchTab] No filter select found in tab:', tabId);
            }
        },
        
        filterResults: function(event) {
            var $select = $(event.currentTarget);
            var filterValue = $select.val();
            var $tabContent = $select.closest('.school-sports-api-tab-content');
            var tabId = $tabContent.attr('id');
            
            var $groupsInTab = $tabContent.find('.school-sports-api-group');
        
            // Hide all groups within the current tab content
            $groupsInTab.hide();
        
            // Show relevant groups
            if (filterValue === 'all') {
                $groupsInTab.show();
            } else {
                // $groupsInTab is already scoped to the current $tabContent
                $groupsInTab.filter('[data-group="' + filterValue + '"]').show();
            }
        
            if (tabId) {
                localStorage.setItem('sports_filter_' + tabId, filterValue);
            }
        },
        
        loadSportsResults: function() {
            console.log('Loading sports results...');
            
            // Get all results containers
            $('.school-sports-api-container').each(function() {
                var $container = $(this);
                
                // Skip live results containers
                if ($container.find('.school-sports-api-live').length > 0) {
                    return;
                }
                
                console.log('Processing container:', $container.attr('id') || 'unnamed');
                
                // Show loading indicator
                var $loading = $('<div class="school-sports-api-loading">' + school_sports_api.translations.loading + '</div>');
                // Don't replace content, just add loading indicator
                $container.find('.school-sports-api-loading').remove(); // Remove any existing loading indicators
                $container.append($loading);
                
                // Get container data attributes
                var sport = $container.data('sport') || 'odbojka';
                var schoolType = $container.data('school-type') || 'ss';
                var schoolYear = $container.data('school-year') || '2024';
                var testing = $container.data('testing') || ''; // Get testing status
                
                console.log('Container data:', { sport: sport, schoolType: schoolType, schoolYear: schoolYear, testing: testing });
                
                // Store current scroll position and active tab
                var verticalScrollPos = $(window).scrollTop();
                var horizontalScrollPos = $(window).scrollLeft();
                var activeTabId = $container.find('.school-sports-api-tab.active').data('tab');
                console.log('[SportsResults.loadSportsResults] Captured activeTabId before AJAX:', activeTabId, 'for container:', $container.attr('id'));
                
                console.log('Storing scroll positions - vertical:', verticalScrollPos, 'horizontal:', horizontalScrollPos);
                console.log('Active tab:', activeTabId);
                
                // Store current filter selections for each tab
                var filterSelections = {};
                $container.find('.school-sports-api-tab-content').each(function() {
                    var tabId = $(this).attr('id');
                    if (tabId) {
                        var $select = $(this).find('.school-sports-api-filter select');
                        if ($select.length > 0) {
                            filterSelections[tabId] = $select.val();
                        }
                    }
                });
                
                console.log('Current filter selections:', filterSelections);
                
                // Make AJAX request
                $.ajax({
                    url: school_sports_api.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'fetch_sports_results',
                        nonce: school_sports_api.nonce,
                        sport: sport,
                        school_type: schoolType,
                        school_year: schoolYear,
                        testing: testing // Pass testing status
                    },
                    success: function(response) {
                        console.log('AJAX success, response received');
                        
                        // Remove loading indicator
                        $loading.remove();
                        
                        // Create a temporary div to parse the response
                        var $temp = $('<div>').html(response);
                        
                        // Check if there are any results in the response
                        var hasResults = $temp.find('.school-sports-api-group').length > 0 ||
                                        $temp.find('.school-sports-api-tab-content').length > 0 ||
                                        $temp.find('table').length > 0;
                        
                        console.log('Response has results:', hasResults);
                        
                        if (hasResults) {
                            // For tabbed content
                            if ($temp.find('.school-sports-api-tab-content').length > 0) {
                                // console.log('Processing tabbed content in AJAX success.');
                                
                                // Replace the tab buttons structure if it exists in the response
                                if ($temp.find('.school-sports-api-tabs').length > 0) {
                                    $container.find('.school-sports-api-tabs').replaceWith($temp.find('.school-sports-api-tabs'));
                                }

                                // Replace the content of each tab
                                $temp.find('.school-sports-api-tab-content').each(function() {
                                    var $newTabContent = $(this);
                                    var tabId = $newTabContent.attr('id');
                                    var $oldTabContent = $container.find('#' + tabId);
                                    
                                    if ($oldTabContent.length > 0 && $oldTabContent[0]) {
                                        $oldTabContent.empty().append($newTabContent.contents()); // This fixed visual concatenation
                                        
                                        // Immediately set the new select to 'all' if possible, for visual default
                                        var $newSelectInTab = $oldTabContent.find('.school-sports-api-filter select');
                                        if ($newSelectInTab.length > 0) {
                                            if ($newSelectInTab.find('option[value="all"]').length > 0) {
                                                $newSelectInTab.val('all');
                                            } else if ($newSelectInTab.find('option').length > 0) {
                                                // Fallback to first option if 'all' is not present
                                                $newSelectInTab.val($newSelectInTab.find('option:first').val());
                                            }
                                            // DO NOT trigger 'change' here; restoreFiltersForContainer will handle it.
                                        }
                                    } else {
                                        // If old tab content doesn't exist, append new one (should ideally not happen if tabs are also replaced)
                                        // $container.append($newTabContent);
                                    }
                                });
                            }
                            // For non-tabbed content (e.g., if shortcode is used without tabs, or API returns flat groups)
                            else if ($temp.find('.school-sports-api-group').length > 0) {
                                // console.log('Processing non-tabbed group content in AJAX success.');
                                // Replace all groups within the container
                                $container.find('.school-sports-api-group').remove(); // Remove old groups
                                $container.append($temp.find('.school-sports-api-group')); // Append new groups
                            } else {
                                // If no specific tab content or groups, but other content, replace container html
                                // This is a fallback, ideally content structure is consistent.
                                // console.log('Processing generic content in AJAX success.');
                                $container.html($temp.html());
                            }
                            
                            // --- Re-initialization Sequence for the current $container ---
                            // 1. Initialize tab visibility and active states for THIS container, restoring the previously active tab
                            // activeTabId is from line 179, captured before the AJAX call
                            SportsResults.initTabs($container, activeTabId);
                            
                            // 2. Restore filters for THIS container (sets select values and triggers 'change' -> filterResults)
                            SportsResults.restoreFiltersForContainer($container);
                            
                            // 3. Restore scroll positions for THIS container
                            SportsResults.restoreAllTableScrollPositionsInContainer($container);

                            // 4. Force re-activation of the correct tab as a final step
                            if (activeTabId) { // activeTabId was captured before the AJAX call
                                var $finalTabToActivate = $container.find('.school-sports-api-tab[data-tab="' + activeTabId + '"]');
                                var $finalContentToActivate = $container.find('#' + activeTabId);

                                if ($finalTabToActivate.length && $finalContentToActivate.length) {
                                    console.log('[SportsResults.loadSportsResults] Force re-activating tab after all processing:', activeTabId);
                                    $container.find('.school-sports-api-tab').removeClass('active');
                                    $container.find('.school-sports-api-tab-content').hide();
                                    $finalTabToActivate.addClass('active');
                                    $finalContentToActivate.show();
                                } else {
                                    console.warn('[SportsResults.loadSportsResults] Force re-activation FAILED to find tab/content for:', activeTabId, 'Container:', $container.attr('id'));
                                    // Fallback: ensure at least the first tab is active if the intended one somehow vanished or IDs changed
                                    if (!$container.find('.school-sports-api-tab.active').length) {
                                        $container.find('.school-sports-api-tab:first').addClass('active');
                                        $container.find('.school-sports-api-tab-content:first').show();
                                        console.log('[SportsResults.loadSportsResults] Fallback: Activated first tab as force re-activation target was not found.');
                                    }
                                }
                            } else {
                                console.log('[SportsResults.loadSportsResults] No activeTabId was stored; cannot force re-activation.');
                            }

                            // Always remove any existing timestamps first to prevent duplication
                            $container.find('.school-sports-api-live-timestamp').remove();
                            
                            // Update timestamp
                            var now = new Date();
                            var timestamp = now.toLocaleTimeString();
                            $container.append('<div class="school-sports-api-live-timestamp">' + school_sports_api.translations.lastUpdated + ': ' + timestamp + '</div>');
                        } else {
                            console.log('No results found in response');
                        }
                        
                        // Restore both vertical and horizontal scroll positions
                        console.log('Restoring scroll positions - vertical:', verticalScrollPos, 'horizontal:', horizontalScrollPos);
                        
                        // Store horizontal scroll in a variable that persists outside the timeout
                        var storedHorizontalScroll = horizontalScrollPos;
                        
                        // Use setTimeout to ensure the browser has time to render the content before scrolling
                        setTimeout(function() {
                            $(window).scrollTop(verticalScrollPos);
                            $(window).scrollLeft(storedHorizontalScroll);
                            console.log('Scroll positions restored');
                            
                            // Add a second timeout to ensure horizontal scroll is maintained
                            setTimeout(function() {
                                $(window).scrollLeft(storedHorizontalScroll);
                                console.log('Horizontal scroll position reinforced:', storedHorizontalScroll);
                            }, 200);
                        }, 300); // Increased timeout to give more time for rendering
                        
                        console.log('Results refresh complete');
                    },
                    error: function(xhr, status, error) {
                        // Remove loading indicator
                        $loading.remove();
                        
                        // Log the error
                        console.error('Error refreshing results:', status, error);
                    }
                });
            });
        },
        
        startRefreshTimer: function() {
            var self = this; // Store reference to this
            
            // Set refresh interval to 1 minute (60000 ms)
            var refreshInterval = parseInt(school_sports_api.refresh_interval) * 1000 || 60000;
            
            // Check for custom refresh interval
            if (typeof school_sports_api_custom_refresh !== 'undefined' && school_sports_api_custom_refresh > 0) {
                refreshInterval = parseInt(school_sports_api_custom_refresh) * 1000;
            }
            
            console.log('Setting SportsResults refresh timer to', refreshInterval, 'ms');
            
            // Clear any existing timer
            if (this.timer) {
                clearInterval(this.timer);
                // console.log('Cleared existing SportsResults timer');
            }
            
            // Set new timer with proper context
            this.timer = setInterval(function() {
                // console.log('SportsResults timer triggered, loading sports results...');
                self.loadSportsResults();
            }, refreshInterval);
            
            // console.log('New SportsResults timer set with ID:', this.timer);
            
            // Also load results immediately when the timer starts
            // console.log('Loading SportsResults immediately as timer starts.');
            self.loadSportsResults();
        },
        
        // The refreshResults method has been replaced by loadSportsResults
        
        // Renamed to be more specific, called by init and AJAX success
        restoreFiltersForContainer: function($containerContext) {
            // console.log('[SportsResults.restoreFiltersForContainer] Restoring filters for container:', $containerContext.attr('id') || 'unnamed');
            // Only process the filter for the currently active and visible tab content within the container
            var $activeTabContent = $containerContext.find('.school-sports-api-tab-content:visible');

            if ($activeTabContent.length > 0) {
                var tabId = $activeTabContent.attr('id');
                var $select = $activeTabContent.find('.school-sports-api-filter select');
                // console.log('[SportsResults.restoreFiltersForContainer] Processing active tab:', tabId);

                if (tabId && $select.length > 0) {
                    var storedValue = localStorage.getItem('sports_filter_' + tabId);
                    var finalValueToSet = null;

                    if (storedValue && $select.find('option[value="' + storedValue + '"]').length > 0) {
                        finalValueToSet = storedValue;
                    } else {
                        if ($select.find('option[value="all"]').length > 0) {
                            finalValueToSet = 'all';
                        } else if ($select.find('option').length > 0) {
                            finalValueToSet = $select.find('option:first').val();
                        }
                    }

                    if (finalValueToSet !== null) {
                        $select.val(finalValueToSet);
                        if (storedValue !== finalValueToSet) {
                            localStorage.setItem('sports_filter_' + tabId, finalValueToSet);
                        }
                        // console.log('[SportsResults.restoreFiltersForContainer] For active tab', tabId, 'set filter to:', finalValueToSet, 'and triggering change.');
                        $select.trigger('change'); // This applies the filter to the now visible tab
                    }
                }
            }
        },

        // Main function to call on init to restore all filters on the page
        restoreFilters: function() {
            // console.log('[SportsResults.restoreFilters] Restoring all filters on page.');
            var self = this;
            $('.school-sports-api-container').not(':has(.school-sports-api-live)').each(function() {
                self.restoreFiltersForContainer($(this));
            });
        },

        getScrollStorageKey: function(groupElement) {
            var $group = $(groupElement);
            var $tabContent = $group.closest('.school-sports-api-tab-content');
            var $container = $group.closest('.school-sports-api-container');

            var containerId = $container.attr('id') || $container.data('sport') || 'unknown-container';
            // This logic should correctly identify 'live' for LiveResults if $tabContent is not found.
            var tabId = $tabContent.length ? $tabContent.attr('id') : ($container.find('.school-sports-api-live').length ? 'live' : 'no-tab');
            var groupId = $group.data('group') || 'unknown-group';
            
            // console.log('Key components:', containerId, tabId, groupId);
            return 'ssa_scroll_' + containerId + '_' + tabId + '_' + groupId;
        },

        saveTableScrollPosition: function(event) {
            var $group = $(event.currentTarget); // event.currentTarget is the .school-sports-api-group
            var scrollLeft = $group.scrollLeft();
            var key = this.getScrollStorageKey($group);
            if (scrollLeft !== undefined) {
                localStorage.setItem(key, scrollLeft);
                // console.log('Saved scroll for group', key, scrollLeft);
            }
        },

        restoreTableScrollPosition: function(groupElement) {
            var $group = $(groupElement);
            var key = this.getScrollStorageKey($group);
            var scrollLeft = localStorage.getItem(key);
            if (scrollLeft !== null) {
                // Use a short timeout to ensure content is rendered before scrolling
                setTimeout(function() {
                    $group.scrollLeft(parseInt(scrollLeft, 10));
                    // console.log('Restored scroll for group', key, scrollLeft);
                }, 50);
            }
        },

        restoreAllTableScrollPositionsInContainer: function(containerElement) {
            var self = this;
            // Find all .school-sports-api-group elements within the given container
            $(containerElement).find('.school-sports-api-group').each(function() {
                self.restoreTableScrollPosition(this);
            });
        },

        getContainerStorageKeySuffix: function($container) {
            if (!$container || !$container.length) {
                // console.warn('[SportsResults.getContainerStorageKeySuffix] Invalid container provided.');
                return 'unknown_container_invalid';
            }
            var id = $container.attr('id');
            if (id) {
                return id;
            }
            // Fallback to data attributes if no ID
            var sport = $container.data('sport') || 'na_sport';
            var schoolType = $container.data('school-type') || 'na_type';
            var schoolYear = $container.data('school-year') || 'na_year';
            var keySuffix = sport + '_' + schoolType + '_' + schoolYear;
            // console.log('[SportsResults.getContainerStorageKeySuffix] Generated key suffix:', keySuffix, 'from data attributes for container:', $container);
            return keySuffix;
        }
    };

    /**
     * Live Results
     */
    var LiveResults = {
        timer: null,
        
        init: function() {
            console.log('LiveResults.init() called');
            
            // First, check if there are any saved filters in localStorage
            var savedFilters = {};
            $('.school-sports-api-live').each(function() {
                var $container = $(this);
                var $parentContainer = $container.closest('.school-sports-api-container');
                var containerId = $parentContainer.attr('id') || 'live_results';
                var savedFilter = localStorage.getItem('live_filter_' + containerId);
                
                if (savedFilter) {
                    savedFilters[containerId] = savedFilter;
                    console.log('Found saved filter for container', containerId, ':', savedFilter);
                }
            });
            
            // Add timestamp immediately to all live containers
            var now = new Date();
            var timestamp = now.toLocaleTimeString();
            $('.school-sports-api-live').each(function() {
                // Remove any existing timestamp first
                $(this).find('.school-sports-api-live-timestamp').remove();
                
                // Add the timestamp
                $(this).append('<div class="school-sports-api-live-timestamp">' + school_sports_api.translations.lastUpdated + ': ' + timestamp + '</div>');
            });
            console.log('Added initial timestamp:', timestamp);
            
            // Create initial filter with "Sve Grupe" option before loading results
            $('.school-sports-api-live').each(function() {
                var $container = $(this);
                var $parentContainer = $container.closest('.school-sports-api-container');
                
                // Remove any existing filter
                $parentContainer.find('.school-sports-api-filter').remove();
                
                // Create new filter
                var $filter = $('<div class="school-sports-api-filter"></div>'); // Style removed
                $container.before($filter);
                
                // Create select element with default option
                var $select = $('<select></select>'); // Style removed
                $select.append($('<option value="all">Sve Grupe</option>'));
                
                // Add select to filter
                $filter.append($select);
                
                console.log('Created initial filter with "Sve Grupe" option');
            });
            
            // Load results immediately
            console.log('Loading live results immediately');
            this.loadLiveResults();
            
            // Start the refresh timer
            console.log('Starting live results refresh timer');
            this.startRefreshTimer();
            
            // Filter visibility is now controlled by CSS defaults.
            
            // Set default filter to "all" (Sve Grupe) immediately and trigger change to apply it
            $('.school-sports-api-filter select').val('all').trigger('change');
            console.log('Initial filter set to "all" (Sve Grupe)');
            
            // Apply again after a short delay to ensure the DOM is fully ready
            setTimeout(function() {
                $('.school-sports-api-live').each(function() {
                    var $container = $(this);
                    var $parentContainer = $container.closest('.school-sports-api-container');
                    var containerId = $parentContainer.attr('id') || 'live_results';
                    var $filter = $parentContainer.find('.school-sports-api-filter select');
                    
                    if ($filter.length) {
                        // If we have a saved filter, use it
                        if (savedFilters[containerId] && $filter.find('option[value="' + savedFilters[containerId] + '"]').length) {
                            console.log('Applying saved filter for container', containerId, ':', savedFilters[containerId]);
                            $filter.val(savedFilters[containerId]).trigger('change');
                        } else {
                            // Ensure "all" is set if no saved filter
                            console.log('Reinforcing default filter "all" after delay');
                            $filter.val('all').trigger('change');
                            // Also save this default to localStorage
                            localStorage.setItem('live_filter_' + containerId, 'all');
                        }
                    }
                });
            }, 500);
            
            console.log('LiveResults initialized');
        },

        loadLiveResults: function() {
            console.log('Loading live results...');
            var $container = $('.school-sports-api-live');
            
            console.log('Live container found:', $container.length);
            if ($container.length === 0) {
                console.log('No live container found, exiting');
                return;
            }
            
            // Store current filter if it exists
            var $parentContainer = $container.closest('.school-sports-api-container');
            var $currentFilter = $parentContainer.find('.school-sports-api-filter');
            var currentFilterValue = $currentFilter.find('select').val();
            console.log('Current filter value from select:', currentFilterValue);
            
            // Store container ID for localStorage
            var containerId = $parentContainer.attr('id') || 'live_results';
            
            // Try to get saved filter from localStorage
            var savedFilter = localStorage.getItem('live_filter_' + containerId);
            console.log('Saved filter from localStorage:', savedFilter);
            
            // Use saved filter if available, otherwise use current filter
            if (savedFilter) {
                currentFilterValue = savedFilter;
                console.log('Using saved filter from localStorage:', savedFilter);
            } else if (currentFilterValue) {
                console.log('Using current filter from select:', currentFilterValue);
            } else {
                console.log('No filter found, using default');
            }
            
            // Store the current filter value in localStorage to ensure it persists
            if (currentFilterValue) {
                localStorage.setItem('live_filter_' + containerId, currentFilterValue);
                console.log('Stored filter value in localStorage:', currentFilterValue);
            }
            
            console.log('Using filter value for refresh:', currentFilterValue);
            
            // Store both vertical and horizontal scroll positions
            var verticalScrollPos = $(window).scrollTop();
            var horizontalScrollPos = $(window).scrollLeft();
            console.log('Storing scroll positions - vertical:', verticalScrollPos, 'horizontal:', horizontalScrollPos);
            
            // Show loading indicator
            var $loading = $('<div class="school-sports-api-loading">' + school_sports_api.translations.loading + '</div>');
            // Don't replace content, just add loading indicator
            $container.find('.school-sports-api-loading').remove(); // Remove any existing loading indicators
            $container.append($loading);
            
            // Get school type if specified
            var schoolType = $container.data('school-type') || '';
            var testing = $container.data('testing') || ''; // Get testing status from live container
            
            // Make AJAX request
            $.ajax({
                url: school_sports_api.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_live_results',
                    nonce: school_sports_api.nonce,
                    school_type: schoolType,
                    testing: testing // Pass testing status
                },
                success: function(response) {
                    // Remove loading indicator
                    $loading.remove();
                    
                    console.log('AJAX success, response length:', response.length);
                    
                    // Create a temporary div to parse the response
                    var $temp = $('<div>').html(response);
                    
                    // Get the current filter value
                    var $parentContainer = $container.closest('.school-sports-api-container');
                    var $filter = $parentContainer.find('.school-sports-api-filter select');
                    var filterValue = $filter.val();
                    
                    console.log('Current filter value before update:', filterValue);
                    
                    // Store the filter value as a data attribute on the container
                    $container.attr('data-current-filter', filterValue);
                    
                    // Update only the groups inside the container, not the entire container
                    var $newGroups = $temp.find('.school-sports-api-group');
                    
                    // Remove existing groups
                    $container.find('.school-sports-api-group').remove();
                    
                    // Add new groups
                    $container.append($newGroups);
                    
                    console.log('Groups updated with response');
                    
                    // Process the response to extract group data for filter
                    // Pass the current filter value to ensure it's preserved
                    console.log('Creating/updating filter with value:', filterValue);
                    LiveResults.createOrUpdateFilter($container, filterValue);
                    
                    // Force the filter to be applied
                    var $newFilter = $parentContainer.find('.school-sports-api-filter select');
                    $newFilter.val(filterValue);
                    $newFilter.trigger('change');
                    
                    console.log('Filter applied with value:', filterValue);
                    
                    // Check if there are any results before showing timestamp
                    var hasResults = $container.find('.school-sports-api-group').length > 0 ||
                                    $container.find('table').length > 0;
                    
                    // Always remove any existing timestamps first to prevent duplication
                    $container.find('.school-sports-api-live-timestamp').remove();
                    
                    // Add timestamp only if there are results
                    if (hasResults) {
                        var now = new Date();
                        var timestamp = now.toLocaleTimeString();
                        $container.append('<div class="school-sports-api-live-timestamp">' + school_sports_api.translations.lastUpdated + ': ' + timestamp + '</div>');
                        
                        // Show the header if there are results
                        $('.school-sports-api-live-header').show();
                        
                        // Apply the filter again to ensure it's properly applied
                        var $parentContainer = $container.closest('.school-sports-api-container');
                        var $filter = $parentContainer.find('.school-sports-api-filter select');
                        var filterValue = $filter.val();
                        
                        console.log('Re-applying filter after timestamp update:', filterValue);
                        $filter.trigger('change');
                    } else {
                        // Hide the header if there are no results
                        $('.school-sports-api-live-header').hide();
                        
                        // Remove any existing "no results" message
                        $container.find('.school-sports-api-no-results').remove();
                        
                        // Add "no results" message
                        $container.append('<div class="school-sports-api-no-results">' + school_sports_api.translations.noResults + '</div>');
                    }
                    
                    // Restore both vertical and horizontal scroll positions
                    console.log('Restoring scroll positions - vertical:', verticalScrollPos, 'horizontal:', horizontalScrollPos);

                    // Restore table scroll positions for the updated container
                    // $container here is the .school-sports-api-live element
                    LiveResults.restoreAllTableScrollPositionsInContainer($container.closest('.school-sports-api-container'));
                    
                    // Store horizontal scroll in a variable that persists outside the timeout
                    var storedHorizontalScroll = horizontalScrollPos;
                    
                    // Use setTimeout to ensure the browser has time to render the content before scrolling
                    // This existing timeout block handles general window scroll.
                    // The table-specific scroll is handled by restoreAllTableScrollPositionsInContainer.
                    setTimeout(function() {
                        $(window).scrollTop(verticalScrollPos);
                        $(window).scrollLeft(storedHorizontalScroll); // This applies to the window, not individual tables
                        console.log('Window scroll positions restored');
                        
                        // Add a second timeout to ensure horizontal scroll is maintained
                        setTimeout(function() {
                            $(window).scrollLeft(storedHorizontalScroll);
                            console.log('Window horizontal scroll position reinforced:', storedHorizontalScroll);
                        }, 200);
                    }, 300); // Increased timeout to give more time for rendering
                },
                error: function() {
                    // Remove loading indicator
                    $loading.remove();
                    
                    // Show error message
                    $container.append('<div class="school-sports-api-error">' + school_sports_api.translations.error + '</div>');
                }
            });
        },

        startRefreshTimer: function() {
            var self = this; // Store reference to this
            
            var refreshInterval = parseInt(school_sports_api.refresh_interval) * 1000;
            
            // Check for custom refresh interval
            if (typeof school_sports_api_custom_refresh !== 'undefined') {
                refreshInterval = parseInt(school_sports_api_custom_refresh) * 1000;
            }
            
            console.log('Setting live results refresh timer to', refreshInterval, 'ms');
            
            // Clear any existing timer
            if (this.timer) {
                clearInterval(this.timer);
                console.log('Cleared existing live results timer');
            }
            
            // Set new timer with proper context
            this.timer = setInterval(function() {
                console.log('Live results timer triggered, loading live results...');
                self.loadLiveResults();
            }, refreshInterval);
            
            console.log('New live results timer set with ID:', this.timer);
        },
        
        // Restore filter values from localStorage
        restoreFilters: function() {
            console.log('Restoring filters from localStorage');
            
            // We don't need to do anything here anymore since:
            // 1. Filter creation is handled in createOrUpdateFilter
            // 2. Filter values are restored during filter creation
            // 3. Filter application happens automatically after creation
            
            // This function is kept for backward compatibility
            // but the actual work is done elsewhere
        },
        
        /**
         * Create or update the filter based on available groups
         */
        createOrUpdateFilter: function($container, currentFilterValue) {
            console.log('Creating/updating filter for live results');
            
            // Find all groups in the container
            var $groups = $container.find('.school-sports-api-group');
            
            if ($groups.length === 0) {
                console.log('No groups found, using basic filter with just "Sve Grupe"');
                
                // Get the parent container
                var $parentContainer = $container.closest('.school-sports-api-container');
                var containerId = $parentContainer.attr('id') || 'live_results';
                
                // Remove any existing filter
                $parentContainer.find('.school-sports-api-filter').remove();
                
                // Create new filter with just the "all" option
                var $filter = $('<div class="school-sports-api-filter"></div>'); // Style removed
                $container.before($filter);
                
                // Create select element with just the "all" option
                var $select = $('<select></select>'); // Style removed
                $select.append($('<option value="all">Sve Grupe</option>'));
                
                // Add select to filter
                $filter.append($select);
                
                // Set the value to "all"
                $select.val('all');
                
                // Store in localStorage
                localStorage.setItem('live_filter_' + containerId, 'all');
                
                // Bind change event
                $select.off('change').on('change', function() {
                    // This is just a placeholder since there's only one option
                    console.log('Filter changed to: all (only option)');
                });
                
                // Remove any existing "no results" message
                $container.find('.school-sports-api-no-results').remove();
                
                // Add "no results" message
                $container.append('<div class="school-sports-api-no-results">' + school_sports_api.translations.noResults + '</div>');
                
                return;
            }
            
            console.log('Found', $groups.length, 'groups for filtering');
            
            // Create filters object
            var filters = {
                'all': 'Sve Grupe'
            };
            
            // Extract gender filters
            var genders = {};
            $groups.each(function() {
                var $group = $(this);
                var gender = $group.attr('data-gender');
                var genderName = '';
                
                // Determine gender name based on gender key
                if (gender === 'mladici') genderName = 'Mladići';
                else if (gender === 'djevojke') genderName = 'Djevojke';
                else if (gender === 'djecaci') genderName = 'Dječaci';
                else if (gender === 'djevojcice') genderName = 'Djevojčice';
                else genderName = gender;
                
                if (gender && !genders[gender]) {
                    genders[gender] = genderName;
                    filters['all-' + gender] = 'Sve - ' + genderName;
                }
                
                // Add group filter
                var groupId = $group.attr('data-group');
                var groupName = $group.find('h4').text();
                if (groupId && groupName) {
                    filters[groupId] = groupName;
                }
            });
            
            // Get the parent container
            var $parentContainer = $container.closest('.school-sports-api-container');
            var containerId = $parentContainer.attr('id') || 'live_results';
            
            // Remove any existing filter
            $parentContainer.find('.school-sports-api-filter').remove();
            
            // Create new filter
            var $filter = $('<div class="school-sports-api-filter"></div>'); // Style removed
            $container.before($filter);
            
            // Create select element
            var $select = $('<select></select>'); // Style removed
            
            // Add options
            $.each(filters, function(value, label) {
                var $option = $('<option></option>').val(value).text(label);
                $select.append($option);
            });
            
            // Add select to filter
            $filter.append($select);
            
            console.log('Filter created with', Object.keys(filters).length, 'options');
            
            // Try to restore from localStorage first, then fall back to current value
            var savedFilter = localStorage.getItem('live_filter_' + containerId);
            console.log('Saved filter from localStorage:', savedFilter);
            
            // Determine which filter value to use
            // First priority: use 'all' as the default filter for new users
            var filterToUse = 'all';
            
            // Second priority: use the passed currentFilterValue if it exists in the filters
            if (currentFilterValue && filters[currentFilterValue]) {
                filterToUse = currentFilterValue;
                console.log('Using passed filter value:', filterToUse);
            }
            // Third priority: use the saved filter from localStorage if it exists in the filters
            else if (savedFilter && filters[savedFilter]) {
                filterToUse = savedFilter;
                console.log('Using saved filter from localStorage:', filterToUse);
            }
            // Default to 'all' if no valid filter is found
            else {
                console.log('No valid filter found, using default "all" (Sve Grupe)');
            }
            
            // Always ensure we have a valid filter value
            if (!filterToUse || !filters[filterToUse]) {
                filterToUse = 'all';
                console.log('Forcing default filter to "all" (Sve Grupe)');
            }
            
            // Set the filter value
            $select.val(filterToUse);
            
            // Store the filter value in localStorage
            localStorage.setItem('live_filter_' + containerId, filterToUse);
            console.log('Filter value set to:', filterToUse);
            
            // Bind change event - use a direct function instead of a method reference
            $select.off('change').on('change', function() {
                var filter = $(this).val();
                var $select = $(this);
                var $container = $select.closest('.school-sports-api-container');
                var containerId = $container.attr('id') || 'live_results';
                
                console.log('Filter changed to:', filter);
                
                // Store the filter value in localStorage immediately
                localStorage.setItem('live_filter_' + containerId, filter);
                console.log('Filter value stored in localStorage:', filter);
                
                // Store the filter value as a data attribute on the container
                $container.attr('data-current-filter', filter);
                
                // Get all groups within the live container
                var $liveContainer = $container.find('.school-sports-api-live');
                var $allGroups = $liveContainer.find('.school-sports-api-group');
                
                console.log('Total groups in live container:', $allGroups.length);
                console.log('Group data:', $allGroups.map(function() {
                    return {
                        'group': $(this).attr('data-group'),
                        'gender': $(this).attr('data-gender')
                    };
                }).get());
                
                if (filter === 'all') {
                    // Show all groups
                    $allGroups.show();
                    console.log('Showing all groups');
                } else if (filter.startsWith('all-')) {
                    // Show all groups for a specific gender
                    var gender = filter.replace('all-', '');
                    console.log('Filtering by gender:', gender);
                    
                    $allGroups.each(function() {
                        var $group = $(this);
                        var groupGender = $group.attr('data-gender');
                        
                        console.log('Group:', $group.attr('data-group'), 'Gender:', groupGender);
                        
                        if (groupGender === gender) {
                            $group.show();
                            console.log('Showing group:', $group.attr('data-group'));
                        } else {
                            $group.hide();
                            console.log('Hiding group:', $group.attr('data-group'));
                        }
                    });
                } else {
                    // Show only the selected group
                    console.log('Filtering by group ID:', filter);
                    
                    $allGroups.each(function() {
                        var $group = $(this);
                        var groupId = $group.attr('data-group');
                        
                        console.log('Group ID:', groupId);
                        
                        if (groupId === filter) {
                            $group.show();
                            console.log('Showing group:', groupId);
                        } else {
                            $group.hide();
                            console.log('Hiding group:', groupId);
                        }
                    });
                }
                
                // Filter visibility is now controlled by CSS defaults.
                // If specific dynamic visibility changes are needed, use class toggling.
            });
            
            // Always apply the filter to ensure it's not reset on auto-refresh
            console.log('Applying filter:', $select.val());
            $select.trigger('change');
            
            console.log('Filter created/updated with', Object.keys(filters).length, 'options');
        },

        // The filterResults function has been moved directly into the createOrUpdateFilter function
        // for better encapsulation and to avoid context issues
    // The previous method createOrUpdateFilter already ends with a comma.
    // This closing brace was prematurely closing the LiveResults object.

        getScrollStorageKey: function(groupElement) {
            var $group = $(groupElement);
            var $container = $group.closest('.school-sports-api-container');
            var containerId = $container.attr('id') || ($container.find('.school-sports-api-live').length ? 'live-results-default' : 'unknown-container');
            var groupId = $group.data('group') || 'unknown-group';
            return 'ssa_scroll_live_' + containerId + '_' + groupId;
        },

        saveTableScrollPosition: function(event) {
            var $group = $(event.currentTarget);
            var scrollLeft = $group.scrollLeft();
            var key = this.getScrollStorageKey($group);
            if (scrollLeft !== undefined) {
                localStorage.setItem(key, scrollLeft);
            }
        },

        restoreTableScrollPosition: function(groupElement) {
            var $group = $(groupElement);
            var key = this.getScrollStorageKey($group);
            var scrollLeft = localStorage.getItem(key);
            if (scrollLeft !== null) {
                 setTimeout(function() {
                    $group.scrollLeft(parseInt(scrollLeft, 10));
                }, 50);
            }
        },

        restoreAllTableScrollPositionsInContainer: function(containerElement) {
            var self = this;
            $(containerElement).find('.school-sports-api-group').each(function() {
                self.restoreTableScrollPosition(this);
            });
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
        // Data attributes (sport, school_type, school_year, testing) are now added directly
        // by the PHP shortcode generation (generate_results_html and live_shortcode).
        // The previous logic here that parsed classes to set data attributes is no longer needed.
        
        // Initialize sports results if present
        if ($('.school-sports-api-container').length > 0 && $('.school-sports-api-live').length === 0) {
            SportsResults.init();
            
            // Force filter to be visible after a short delay
            setTimeout(function() {
                // Filter visibility is now controlled by CSS defaults.
                // Ensure styles are applied if they were somehow unset.
                // This might not be necessary if CSS is robust.
                $('.school-sports-api-filter').show(); // Simpler way if display:none was set by other JS
                $('.school-sports-api-filter select').show();

                // Re-bind event handler
                $('.school-sports-api-filter select').off('change').on('change', SportsResults.filterResults);
            }, 1000);
        }
        
        // Initialize live results if present
        if ($('.school-sports-api-live').length > 0) {
            console.log('Live results container found, initializing...');
            LiveResults.init();
            
            // Initialize WebSocket connection if available
            if (typeof school_sports_api.ws_url !== 'undefined') {
                WebSocketConnection.init();
            }
        }
        
        // Add timestamp to regular results
        if ($('.school-sports-api-container').length > 0 && $('.school-sports-api-live').length === 0) {
            var now = new Date();
            var timestamp = now.toLocaleTimeString();
            $('.school-sports-api-container').append('<div class="school-sports-api-live-timestamp">' + school_sports_api.translations.lastUpdated + ': ' + timestamp + '</div>');
        }
    });

    // Translations object is now provided by wp_localize_script via school_sports_api.translations

    /**
     * Button Visibility for Elementor Buttons
     */
    var ButtonVisibility = {
        init: function() {
            // Apply immediately
            this.applyButtonVisibility();
            
            // Apply on document ready
            $(document).ready(this.applyButtonVisibility);
            
            // Also run on Elementor frontend init, which happens after the page is fully loaded
            if (typeof window.elementorFrontend !== 'undefined' && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/global', this.applyButtonVisibility);
            }
            
            // Apply multiple times with delays to ensure it catches Elementor's rendering
            setTimeout(this.applyButtonVisibility, 100);
            setTimeout(this.applyButtonVisibility, 500);
            setTimeout(this.applyButtonVisibility, 1000);
            setTimeout(this.applyButtonVisibility, 2000);
            
            // Apply on window resize
            $(window).on('resize', this.applyButtonVisibility);
            
            // Apply on any AJAX completion
            $(document).ajaxComplete(this.applyButtonVisibility);
        },
        
        applyButtonVisibility: function() {
            console.log('Applying button visibility settings');
            
            // Check if button visibility settings are available
            if (typeof school_sports_api !== 'undefined' && typeof school_sports_api.desktop_button_visible !== 'undefined') {
                // Apply desktop button visibility
                if (!school_sports_api.desktop_button_visible) {
                    $('body').addClass('desktop-button-hidden');
                    console.log('Desktop button hidden by settings');
                } else {
                    $('body').removeClass('desktop-button-hidden');
                }
            }
            
            if (typeof school_sports_api !== 'undefined' && typeof school_sports_api.mobile_button_visible !== 'undefined') {
                // Apply mobile button visibility
                if (!school_sports_api.mobile_button_visible) {
                    $('body').addClass('mobile-button-hidden');
                    console.log('Mobile button hidden by settings');
                } else {
                    $('body').removeClass('mobile-button-hidden');
                }
            }
            
            // Target Elementor buttons with all possible selectors
            var desktopSelectors = [
                '.elementor-widget-button[data-id="DesktopButton"]',
                '.elementor-element[data-id="DesktopButton"]',
                '#DesktopButton',
                'div[data-id="DesktopButton"]',
                '[data-id="DesktopButton"]',
                '[id="DesktopButton"]',
                '[class*="DesktopButton"]'
            ].join(',');
            
            var mobileSelectors = [
                '.elementor-widget-button[data-id="MobileButton"]',
                '.elementor-element[data-id="MobileButton"]',
                '#MobileButton',
                'div[data-id="MobileButton"]',
                '[data-id="MobileButton"]',
                '[id="MobileButton"]',
                '[class*="MobileButton"]'
            ].join(',');
            
            var desktopButton = $(desktopSelectors);
            var mobileButton = $(mobileSelectors);
            
            // Apply inline styles directly to elements for maximum override
            if (typeof school_sports_api !== 'undefined' && typeof school_sports_api.desktop_button_visible !== 'undefined') {
                if (!school_sports_api.desktop_button_visible) {
                    desktopButton.css({
                        'display': 'none !important',
                        'visibility': 'hidden !important',
                        'opacity': '0 !important',
                        'position': 'absolute !important',
                        'pointer-events': 'none !important',
                        'width': '0 !important',
                        'height': '0 !important',
                        'margin': '0 !important',
                        'padding': '0 !important',
                        'overflow': 'hidden !important'
                    });
                    desktopButton.attr('style', function(i, style) {
                        return (style || '') + 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
                    });
                    desktopButton.hide();
                    
                    // Also hide parent elements if they only contain this button
                    desktopButton.parents().each(function() {
                        var $parent = $(this);
                        if ($parent.children().length === 1 && $parent.children().is(desktopButton)) {
                            $parent.css('display', 'none !important');
                        }
                    });
                }
            }
            
            if (typeof school_sports_api !== 'undefined' && typeof school_sports_api.mobile_button_visible !== 'undefined') {
                if (!school_sports_api.mobile_button_visible) {
                    mobileButton.css({
                        'display': 'none !important',
                        'visibility': 'hidden !important',
                        'opacity': '0 !important',
                        'position': 'absolute !important',
                        'pointer-events': 'none !important',
                        'width': '0 !important',
                        'height': '0 !important',
                        'margin': '0 !important',
                        'padding': '0 !important',
                        'overflow': 'hidden !important'
                    });
                    mobileButton.attr('style', function(i, style) {
                        return (style || '') + 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
                    });
                    mobileButton.hide();
                    
                    // Also hide parent elements if they only contain this button
                    mobileButton.parents().each(function() {
                        var $parent = $(this);
                        if ($parent.children().length === 1 && $parent.children().is(mobileButton)) {
                            $parent.css('display', 'none !important');
                        }
                    });
                }
            }
            
            if (desktopButton.length) {
                console.log('Found desktop button:', desktopButton.length);
            }
            
            if (mobileButton.length) {
                console.log('Found mobile button:', mobileButton.length);
            }
        }
    };
    
    // Initialize button visibility
    ButtonVisibility.init();

})(jQuery);