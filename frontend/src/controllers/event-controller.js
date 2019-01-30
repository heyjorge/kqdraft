angular.module('event.ctrl', [])

.controller('eventCtrl', ['$scope', '$state', '$stateParams', 'kqDraftFactory', 'events', 'location',
    function ($scope, $state, $stateParams, kqDraftFactory, events, location) {
        $scope.events = events.data;
        $scope.location = location.data[0];

        $scope.createEvent = function() {
            kqDraftFactory.createEvent($stateParams.locationId)
            .then(
                function(response) {
                    $state.go('.', $stateParams, { reload: true });
                }
            );
        };
    }
]);