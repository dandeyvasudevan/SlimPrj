var module = angular.module("sampleApp", ['ngRoute','ngGrid']);

module.config(['$routeProvider',
    function($routeProvider, $routeParams) {
        $routeProvider.
            when('/home/:option', {
                templateUrl: 'templates/home.html',
                /*templateUrl: function(params){
                    return 'templates/'+params.option+'.html';
                },*/
                controller: 'HomeController',
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
    function($rootScope, $scope, $location, $http, $window, $routeParams){
   
    $scope.login = function() {
       //alert('login username:'+$scope.username+', password:'+$scope.password);
       
       $http.get(restAPI+'token/'+$scope.username+'/'+$scope.password+'/web2').
                success(function(data) {
                   if(data.error)
                       $scope.errorMsg = data.message;
                   else {
                       $window.sessionStorage.token =   data.token;
                       $window.sessionStorage.userID=   data.userID;
                       $location.path('/home/watchlists');
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
    function($rootScope, $scope, $location, $http, $window, $routeParams){
        $scope.title = 'Watch Lists';
        $scope.routeParams = $routeParams;
        
        $scope.displayWatchlists = function() {
                $http.get(restAPI+'watchlists',
                            {headers: {'Authorizationtoken': 'Bearer '+$window.sessionStorage.token}
                        }).
                        success(function(data) {
                            $scope.watchlists = data.watchlists;
                            console.log( $scope.watchlists );
                        });
                        
                        
                        $scope.gridOptions  =   {
                            data: 'watchlists',
                            multiSelect: false, 
                            showGroupPanel: false,
                            columnDefs: [{field: 'instrument', displayName: 'Instrument'}, 
                                         {field: 'weekly', displayName: 'Weekly'},
                                         {field: 'candlestick', displayName: 'CandleStick'},
                                         {field: 'notes', displayName: 'Notes'},
                                         {field: 'date', displayName: 'Date'},
                                         {field:'id',displayName:'View',  
                                            cellTemplate:'<div class="ngCellText" ng-class="col.colIndex()"><a ng-click="loadById(row)">View</a></div>' 
                                         },
                                         {field:'id', displayName:'Delete',
                                             cellTemplate: '<div class="ngCellText" ng-class="col.colIndex()"><a ng-click="removeById(row)">Delete</a></div>'
                                         },
                                         {field:'id', displayName:'Edit',
                                             cellTemplate: '<div class="ngCellText" ng-class="col.colIndex()"><a ng-click="editById(row)">Edit</a></div>'
                                         }
                                        ]
                        }; 
                        
                        
        };       
        
        $scope.watchlist = {};
        
        $scope.loadById = function(row) {  
            window.console && console.log(row.entity);
            $scope.toggleModal(row.entity);
            //$window.location.href= 'newPage/?id='+ row.entity.id;
        };
        
        $scope.removeById = function(row) {
            var id = row.entity.id;
            
            window.console && console.log(id);
            $http.delete(restAPI+'watchlists/'+id,
                            {headers: {'Authorizationtoken': 'Bearer '+$window.sessionStorage.token}
                        }).
                        success(function(data,status, header, config) {
                            console.log( data );
                            console.log( header );
                            $scope.displayWatchlists();
                        });
        }   
        
        $scope.editById = function(row){
            console.log($scope.addWatchlist);
            var original = $scope.watchlist;
            
            var data = row.entity;
            
            $scope.watchlist = {};
            
            $scope.watchlist.ins    =   row.entity.instrument;
            $scope.watchlist.dly    =   row.entity.daily;
            $scope.watchlist.wkly   =   row.entity.weekly;
            $scope.watchlist.cdlst  =   row.entity.candlestick;
            $scope.watchlist.resmaj =   row.entity.resistancemajor;
            $scope.watchlist.resmin =   row.entity.resistanceminor;
            $scope.watchlist.suppmaj=   row.entity.supportmajor;
            $scope.watchlist.suppmin=   row.entity.supportminor;
            $scope.watchlist.nt     =   row.entity.notes;
            $scope.watchlist.id     =   row.entity.id;
            $scope.action = "Edit";
            $scope.modalShown2 = true;
        };
        
        $scope.logout = function() {
            var localStorage = $window.sessionStorage;
            var token = localStorage.token;
            var userID= localStorage.userID;
            
            $http.get(restAPI+'logout/'+token+'/'+userID           
                        ).
                        success(function(data) {
                            if(!data.error) {
                                $("#grid").remove();
                                localStorage.clear();
                                $location.path('/');
                            }
                        });
        };           
         
        $scope.toggleModal = function($entity) {
            $scope.modalShown1 = !$scope.modalShown1;
            $scope.watchlist = $entity;
        };
  
        
        $scope.addWatchlistItem = function(){
            $scope.watchlist = {};
            $scope.action = "Add";
            $scope.modalShown2 = !$scope.modalShown2;
        }
        
        $scope.submitForm = function(form) {
            $scope.lst = {};    
            //window.console && console.log(JSON.stringify(form.ins));
            //window.console && console.log(JSON.stringify(form.nt));
            //window.console && console.log(form.ins);
            
            var dt  = new Date();
            var dt1 = dt.getFullYear()+'-'+parseInt(dt.getMonth()+1)+'-'+dt.getDate();
            
            $scope.lst.user_id  = $window.sessionStorage.userID;
            $scope.lst.date     = dt1.toString();
            $scope.lst.instrument = form.ins;
            $scope.lst.weekly   = form.wkly;
            $scope.lst.daily    = form.dly;
            $scope.lst.candlestick = form.cdlst;
            $scope.lst.resistancemajor = form.resmaj;
            $scope.lst.resistanceminor = form.resmin;
            $scope.lst.supportmajor = form.suppmaj;
            $scope.lst.supportminor = form.suppmin;
            $scope.lst.notes = form.nt;
            //window.console && console.log(JSON.stringify($scope.lst));
            
            //window.console && console.log(form.id);
            if( form.id ) {
                //alert('update');
                $scope.updateData('watchlists', JSON.stringify($scope.lst), form.id);
            } else {
                $scope.saveData('watchlists', JSON.stringify($scope.lst));
            }
        };
        
        $scope.saveData = function(model, data) {
            $http.post(restAPI+model, data,
                        {headers: {'Authorizationtoken': 'Bearer '+$window.sessionStorage.token}
                       }).
                       success(function(data,status, header, config) {
                            //console.log( data );
                            //console.log( header );
                            //$scope.watchlist = {};
                            
                            $scope.modalShown2 = false;
                            //$scope.watchlist.ins.$setValidity(false);
                            $scope.displayWatchlists();
                        });
        };
        
        $scope.updateData = function(model, data, id){
            $http.put(restAPI+model+'/'+id, data,
                        {headers: {'Authorizationtoken': 'Bearer '+$window.sessionStorage.token}
                       }).
                       success(function(data,status, header, config) {
                            //console.log( data );
                            //console.log( header );
                            $scope.watchlist = {};
                            $scope.modalShown2 = false;
                            $scope.displayWatchlists();
                        });
        };
        
        $scope.displayWatchlists();
});

module.directive('modalDialog', function() {
  return {
    restrict: 'E',
    scope: {
      show: '='
    },
    replace: true, // Replace with the template below
    transclude: true, // we want to insert custom content inside the directive
    link: function(scope, element, attrs) {
      scope.dialogStyle = {};
      if (attrs.width)
        scope.dialogStyle.width = attrs.width;
      if (attrs.height)
        scope.dialogStyle.height = attrs.height;
      scope.hideModal = function() {
        scope.show = false;
      };
    },
    template: "<div class='ng-modal' ng-show='show'><div class='ng-modal-overlay' ng-click='hideModal()'></div><div class='ng-modal-dialog' ng-style='dialogStyle'><div class='ng-modal-close' ng-click='hideModal()'>X</div><div class='ng-modal-dialog-content' ng-transclude></div></div></div>"
  };
});
