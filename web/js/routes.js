var module = angular.module("sampleApp", ['ngRoute']);

module.config(['$routeProvider',
    function($routeProvider) {
        $routeProvider.
            when('/home', {
                templateUrl: 'templates/home.html',
                controller: 'HomeController'
            }).
            otherwise({
                redirectTo: '/',
                templateUrl: 'templates/login.html',
                controller: 'LoginController'
            });
    }]);

var restAPI = 'http://localhost/Slim-master/v1/';
//var restAPI = 'http://demoeappstech.uk/v1/';

module.controller("RouteController", function($scope, $routeParams) {
    $scope.param = $routeParams.param;
});

module.controller("LoginController", 
    function($rootScope, $scope, $location, $http, $window){
   
    $scope.login = function() {
       //alert('login username:'+$scope.username+', password:'+$scope.password);
       
       $http.get(restAPI+'token/'+$scope.username+'/'+$scope.password+'/web2').
                success(function(data) {
                   if(data.error)
                       $scope.errorMsg = data.message;
                   else {
                       $window.sessionStorage.token = data.token;
                       $location.path('/home');
                   }
                })
                .error(function(){
                    delete $window.sessionStorage.token;
                    $rootScope.$broadcast('login failed');
                })
    }; 
    
    if($window.sessionStorage.token)
        $location.path('/home');
});

module.controller("HomeController",
    function($rootScope, $scope, $location, $http, $window){
        $http.get(restAPI+'watchlists',
                    {headers: {'Authorizationtoken': 'Bearer '+$window.sessionStorage.token}
                }).
                success(function(data) {
                    $scope.watchlists = data.watchlists;
                    console.log( $scope.watchlists );
                });
                
        
                     
                     
});