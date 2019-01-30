angular.module('kqdraft.routes', [])

.config(
    function($stateProvider, $urlRouterProvider) {

        $urlRouterProvider.when('', '/location');

        var locationState = {
            name: 'location',
            url: '/location',
            controller: 'locationsCtrl',
            templateUrl: 'views/locations-tpl.html',
            resolve: {
                locations: function(kqDraftFactory) {
                    return kqDraftFactory.getLocations();
                }
            }
        };

        var eventState = {
            name: 'event',
            url: '/location/{locationId}/event',
            controller: 'eventCtrl',
            templateUrl: 'views/events-tpl.html',
            resolve: {
                events: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getEvents($stateParams.locationId);
                },

                location: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getLocation($stateParams.locationId);
                }
            }
        };

        var managePlayersState = {
            name: 'managePlayers',
            url: '/location/{locationId}/event/{eventId}/manage-players',
            controller: 'managePlayersCtrl',
            templateUrl: 'views/manage-players-tpl.html',
            resolve: {
                eventPlayers: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getEventPlayers($stateParams.locationId, $stateParams.eventId);
                },

                preferences: function(kqDraftFactory) {

                    return kqDraftFactory.getPreferences();
                }
            }
        };

        var seriesState = {
            name: 'series',
            url: '/location/{locationId}/event/{eventId}/series/{seriesId}?toggleManagePlayers&slideId',
            controller: 'seriesCtrl',
            templateUrl: 'views/series-tpl.html',
            resolve: {
                series: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getSeries($stateParams.locationId, $stateParams.eventId);
                },

                seriesPlayers: function($stateParams, kqDraftFactory, series) {
                    if ($stateParams.seriesId === "" && series.data.length > 0) {
                        $stateParams.seriesId = series.data[0].id_draft_series;
                    }

                    if ($stateParams.seriesId) {
                        return kqDraftFactory.getSeriesPlayers(
                            $stateParams.locationId,
                            $stateParams.eventId,
                            $stateParams.seriesId
                        );
                    }

                    return { data: [] };
                },

                currentSeriesKey: function($stateParams, seriesService, series) {
                    return seriesService.getCurrentSeriesKey(series.data.reverse(), $stateParams.seriesId);
                },

                bluePlayers: function(seriesPlayers, seriesService) {
                    var sortedPlayers = seriesService.sortSeriesPlayers(seriesPlayers.data);

                    return sortedPlayers.blue;
                },

                goldPlayers: function(seriesPlayers, seriesService) {
                    var sortedPlayers = seriesService.sortSeriesPlayers(seriesPlayers.data);

                    return sortedPlayers.gold;
                },

                location: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getLocation($stateParams.locationId);
                },

                event: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getEvent($stateParams.locationId, $stateParams.eventId);
                },

                players: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getPlayers($stateParams.locationId);
                },

                eventPlayers: function($stateParams, kqDraftFactory) {
                    return kqDraftFactory.getEventPlayers($stateParams.locationId, $stateParams.eventId);
                },

                availableEventPlayers: function(seriesService, eventPlayers, seriesPlayers) {
                    return seriesService.getAvailableEventPlayers(eventPlayers, seriesPlayers);
                },

                preferences: function(kqDraftFactory) {

                    return kqDraftFactory.getPreferences();
                },

                cabsides: function(kqDraftFactory) {
                    return kqDraftFactory.getCabsides();
                },

                maps: function(kqDraftFactory) {
                    return kqDraftFactory.getMaps();
                },
            }
        };

        $stateProvider.state(locationState);
        $stateProvider.state(eventState);
        $stateProvider.state(managePlayersState);
        $stateProvider.state(seriesState);
    }
);