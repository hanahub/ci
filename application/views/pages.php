<script>
    angular.module('PagesModule', [])
        .controller('PagesController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
            $scope.pages = <?php echo json_encode($pages); ?>;
            $scope.pos = 0;                        
            $scope.current = $scope.pages[$scope.pos];            
            
            $scope.item_clicked = function(n) {
                $scope.pos = n;
                $scope.current = $scope.pages[n];                                    
            }
            
        }]); 
</script>


<div class="right" ng-app="PagesModule" ng-controller="PagesController">
    <div class="right_content">
        <div class="right_header">            
            <span class="left_side">
                <span class="title">Pages</span>
                <span class="count" ng-if="!!pages.length">{{pages.length}}</span>
            </span>
            <span class="right_side"><a class="btn" href="<?php echo base_url() . "post"; ?>">Add New Page</a></span>
        </div>
        <div class="main_section">
            <div class="sidebar_col">
                <div class="sidebar_col_content">                                
                    <a class="item" ng-class="{'selected' : $index == pos}" ng-repeat="page in pages track by $index" ng-click="item_clicked($index)">
                        <span>
                            <i class="fb_icon fb_picture_wrap" ng-if="!!page.picture['url']"><img ng-src="{{page.picture['url']}}"/></i>
                            <i class="fb_icon fb_icon_page" ng-if="!page.picture['url']"></i>
                            <span class="fb_value">{{page.name}}</span>
                        </span>
                    </a>                                
                </div>
            </div>
            <div class="main_col">
                <div class="main_col_header" ng-if="!!pages.length">
                    <div class="main_icon_wrap">
                        <i class="fb_icon fb_picture_wrap" ng-if="!!current.picture['url']"><img ng-src="{{current.picture['url']}}"/></i>
                        <i class="fb_icon fb_icon_page" ng-if="!current.picture['url']"></i>                        
                    </div>
                    <div class="main_info">
                        <div class="main_title">{{current.name}}</div>
                        <div class="main_info_body">                            
                            <ul class="info_list"><li></li><li style="padding-left: 0px;"><a target="_blank" ng-href="{{current.link}}">{{current.link}}</a></li></ul>
                            <ul class="info_list"><li>Page #</li><li>{{current.id}}</li></ul>
                            <ul class="info_list"><li>Likes</li><li>{{current.likes}}</li></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        