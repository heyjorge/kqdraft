angular.module('kqdraft.factory', [])

.factory('kqDraftFactory', ['$http', 'API',
    function($http, API) {
        var kqDraftFactory = {
            addLocation: function(locationName) {
                return $http.post(
                    API.url + '/location',
                   { name : locationName }
                );
            },

            addEventPlayer: function(locationId, eventId, playerId) {
                return $http.post(
                    API.url + '/location/' + locationId + '/event/' + eventId + '/player',
                    { id_player : playerId }
                );
            },

            addPlayer: function(locationId, name, preferenceId) {
                return $http.post(
                    API.url + '/location/' + locationId + '/player',
                    { name: name, id_preference: preferenceId }
                );
            },

            addSeriesPlayer: function(locationId, eventId, seriesId, draftEventPlayerId, cabinetSideId, isQueen) {
                return $http.post(
                    API.url + '/location/' + locationId + '/event/' + eventId + '/series/' + seriesId + '/player',
                    {
                        id_draft_event_player: draftEventPlayerId,
                        id_cabinet_side: cabinetSideId,
                        is_queen: isQueen
                    }
                );
            },

            createEvent: function(locationId) {
                return $http.post(
                    API.url + '/location/' + locationId + '/event'
                );
            },

            draftSeries: function(locationId, eventId) {
                return $http.post(
                    API.url + '/location/' + locationId + '/event/' + eventId + '/series/draft'
                );
            },

            endEvent: function(locationId, eventId) {
                var now = new Date();
                var dateEnd = [
                    now.getFullYear(),
                    '-',
                    now.getMonth() + 1,
                    '-',
                    now.getDate(),
                    ' ',
                    now.getHours(),
                    ':',
                    now.getMinutes(),
                    ':',
                    now.getSeconds()
                ].join('');
                return $http.put(
                    API.url + '/location/' + locationId + '/event/' + eventId,
                    { date_end: dateEnd }
                );
            },

            getLocation: function(locationId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId
                );
            },

            getLocations: function() {
                return $http.get(
                    API.url +
                    '/location'
                );
            },

            getEvent: function(locationId, eventId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId + '/event/' + eventId
                );
            },

            getEvents: function(locationId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId + '/event'
                );
            },

            getSeries: function(locationId, eventId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId + '/event/' + eventId + '/series'
                );
            },

            getSeriesPlayers: function(locationId, eventId, seriesId) {
                if (seriesId !== undefined) {
                    return $http.get(
                        API.url +
                        '/location/' + locationId +
                        '/event/' + eventId +
                        '/series/' + seriesId +
                        '/player'
                    );
                }

                return [];
            },

            getPlayers: function(locationId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId + '/player'
                );
            },

            getEventPlayers: function(locationId, eventId) {
                return $http.get(
                    API.url +
                    '/location/' + locationId + '/event/' + eventId + '/player'
                );
            },

            getPreferences: function() {
                return $http.get(
                    API.url +
                    '/preference'
                );
            },

            getCabsides: function() {
                return $http.get(
                    API.url +
                    '/cabside'
                );
            },

            getMaps: function() {
                return $http.get(
                    API.url +
                    '/map'
                );
            },

            removeSeriesPlayer: function(locationId, eventId, seriesId, seriesPlayerId) {
                return $http.delete(
                    API.url +
                        '/location/' + locationId + '/event/' +
                        eventId + '/series/' + seriesId + '/player/' + seriesPlayerId
                );
            },

            submitWin: function(locationId, eventId, seriesId, mapId, cabinetSideId) {
                return $http.post(
                    API.url + '/location/' + locationId + '/event/' + eventId + '/series/' + seriesId + '/round',
                    { id_map: mapId, id_cabinet_side: cabinetSideId, is_winner: true }
                );
            },

            updateEventPlayer: function(locationId, eventId, playerId, isActive) {
                return $http.put(
                    API.url + '/location/' + locationId + '/event/' + eventId + '/player',
                    {
                        id_player : playerId,
                        id_location : locationId,
                        id_event : eventId,
                        is_active : isActive
                    }
                );
            },

            updatePlayerPreference: function(locationId, playerId, playerName, preferenceId) {
                return $http.put(
                    API.url + '/location/' + locationId + '/player/' + playerId,
                    { name: playerName, id_preference: preferenceId }
                );
            },

            updateSeriesPlayer: function(locationId, eventId, seriesId, seriesPlayerId, isQueen, cabinetSideId) {
                return $http.put(
                    API.url +
                        '/location/' + locationId +
                        '/event/' + eventId +
                        '/series/' + seriesId +
                        '/player/' + seriesPlayerId,
                    { is_queen : isQueen, id_cabinet_side: cabinetSideId }
                );
            }
        };

        return kqDraftFactory;
    }
]);