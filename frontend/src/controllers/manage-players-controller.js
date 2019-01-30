angular.module('managePlayers.ctrl', [])

.controller('managePlayersCtrl', ['$scope', '$state', '$stateParams', 'kqDraftFactory', 'eventPlayers', 'preferences',
    function ($scope, $state, $stateParams, kqDraftFactory, eventPlayers, preferences) {
        $scope.eventPlayers = eventPlayers.data;
        $scope.preferences = preferences.data;

        $scope.backToEvent = function() {
            $state.go('series', $stateParams);
        };

        $scope.updatePlayerPreference = function(playerId, playerName, preferenceId) {
            kqDraftFactory.updatePlayerPreference($stateParams.locationId, playerId, playerName, preferenceId)
            .then(
                function(response) {
                    $state.go('.', $stateParams, { reload: true });
                }
            );
        };
    }
]);