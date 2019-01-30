var kqdraft = angular.module(
    'kqdraft',
    [
        'ui.router',
        'ui.bootstrap',
        'templates',
        'ui.sortable',
        'kqdraft.routes',
        'kqdraft.factory',
        'locations.ctrl',
        'event.ctrl',
        'series.ctrl',
        'managePlayers.ctrl',
        'series.service'
    ]
)

.constant('API',
{
    url: 'http://127.0.0.1'
})

.controller('KqDraftController', [
    function() {

    }
]);