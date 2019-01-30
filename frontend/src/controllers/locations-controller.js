angular.module('locations.ctrl', [])

.controller('locationsCtrl', ['$scope', '$state', '$stateParams', 'kqDraftFactory', 'locations',
    function ($scope, $state, $stateParams, kqDraftFactory, locations) {
        $scope.newLocation = {};
        $scope.isCollapsed = true;
        $scope.addLocationError = false;
        $scope.locations = locations.data;

        $scope.addLocation = function() {
            kqDraftFactory.addLocation($scope.newLocation.name)
            .then(
                function(response) {
                    $state.go('.', $stateParams, { reload: true });
                },

                function(failure) {
                    $scope.addLocationError = true;
                }
            );
        };
    }
]);