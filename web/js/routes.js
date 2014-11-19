var module = angular.module("sampleApp", ['ngRoute']);

module.config(['$routeProvider',
    function($routeProvider) {
        $routeProvider.
            when('/route2', {
                templateUrl: 'angular-route-template-2.jsp',
                controller: 'RouteController'
            }).
            otherwise({
                redirectTo: '/',
                templateUrl: 'templates/login.html',
            });
    }]);

module.controller("RouteController", function($scope, $routeParams) {
    $scope.param = $routeParams.param;
})