angular.module('series.service', [])

.service('seriesService', [
    function() {
        var seriesService = {
            getCurrentSeriesKey: function(seriesAll, seriesId) {
                var currentSeriesKey = seriesAll.length-1;

                angular.forEach(seriesAll, function(series, seriesKey) {
                    if (parseInt(seriesId) === series.id_draft_series) {
                        currentSeriesKey = seriesKey;
                    }
                });

                return currentSeriesKey;
            },

            getAvailableEventPlayers: function(eventPlayers, seriesPlayers) {
                var eventPlayersData = JSON.parse(JSON.stringify(eventPlayers.data));
                var seriesPlayersData = seriesPlayers.data;
                var totalEventPlayers = eventPlayersData.length;

                for (sIndex = 0; sIndex < seriesPlayersData.length; sIndex++) {
                    for (eIndex = 0; eIndex < eventPlayersData.length; eIndex++) {
                        if (
                            eventPlayersData[eIndex].id_draft_event_player ===
                            seriesPlayersData[sIndex].id_draft_event_player ||
                            eventPlayersData[eIndex].player_active === false
                        ) {
                            eventPlayersData.splice(eIndex, 1);
                        }
                    }
                }

                return eventPlayersData;
            },

            sortSeriesPlayers: function(seriesPlayers) {
                var bluePlayers = [];
                var goldPlayers = [];

                angular.forEach(seriesPlayers, function(seriesPlayer, seriesPlayerKey) {
                    if (seriesPlayer.id_cabinet_side === 1) {
                        if (seriesPlayer.is_queen === true) {
                            bluePlayers.unshift(seriesPlayer);
                        }
                        else {
                            bluePlayers.push(seriesPlayer);
                        }
                    }
                    else {
                        if (seriesPlayer.is_queen === true) {
                            goldPlayers.unshift(seriesPlayer);
                        }
                        else {
                            goldPlayers.push(seriesPlayer);
                        }
                    }
                });

                return { blue: bluePlayers, gold: goldPlayers };
            },

            hasEnoughPlayers: function(eventPlayers) {
                var queenCount = 0;
                var flexCount = 0;
                var droneCount = 0;

                angular.forEach(eventPlayers, function(eventPlayer, eventPlayerKey) {
                    if (eventPlayer.is_in_event && eventPlayer.id_preference === 1) {
                        queenCount++;
                    }

                    if (eventPlayer.is_in_event && eventPlayer.id_preference === 2) {
                        droneCount++;
                    }

                    if (eventPlayer.is_in_event && eventPlayer.id_preference === 3) {
                        flexCount++;
                    }
                });

                var totalCount = queenCount + flexCount + droneCount;
            }
        };

        return seriesService;
    }
]);