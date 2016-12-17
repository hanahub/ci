<script>
    angular.module('AdAccountsModule', [])
        .controller('AdAccountsController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
            $scope.ad_accounts = <?php echo json_encode($ad_accounts); ?>;
            $scope.pos = 0;                        
            $scope.current = $scope.ad_accounts[$scope.pos];            
            get_ads($scope.current.id);
            
            $scope.item_clicked = function(n) {
                if ($scope.pos == n) return;
                $scope.pos = n;
                $scope.current = $scope.ad_accounts[n];                                    
                get_ads($scope.current.id);
            }
            
            var table;
            function get_ads(account_id) {
                return;
                var url = base_url + 'MY_Facebook/get_ads/' + account_id;
                
                if (typeof table == "undefined") {
                    $.fn.DataTable.ext.errMode = function (data) {
                        console.log(data);
                    }
                    table = $(".fb_datatable").DataTable({
                            "processing": false,
                            "serverSide": true,
                            "ajax": {
                                "url": url,                                
                            },
                            "columnDefs": [
                            {
                                "render": function ( data, type, row ) {
                                    return data;
                                    return data +' ('+ row['adset_id']+')';
                                },
                                "targets": 1
                            }],
                            "lengthMenu": [ 10, 15, 25, 50, 75, 100 ],
                            "searching": false,
                            "ordering": false,
                            "columns": [
                                { "data": "ad_id" },
                                { "data": "adset_id" },
                            ],
                            "pageLength": 10,
                            "rowCallback": function( row, data, index ) {
                                $('td:eq(0)', row).html( index + 1 );
                            },
                            "drawCallback": function( data ) {
                                if (data.json.error) {
                                    alert(data.json.message);
                                }
                            },
                        });
                } else {
                    table.ajax.url( url ).load(function(response) {
                        console.log(response);
                    });
                }
                    
            }            
        }]); 
</script>


<div class="right" ng-app="AdAccountsModule" ng-controller="AdAccountsController">
    <div class="right_content">
        <div class="right_header">            
            <span class="left_side">
                <span class="title">Ad Accounts</span>
                <span class="count" ng-if="!!ad_accounts.length">{{ad_accounts.length}}</span>
            </span>            
        </div>
        <div class="main_section">
            <div class="sidebar_col">
                <div class="sidebar_col_content">                                
                    <a class="item" ng-class="{'selected' : $index == pos}" ng-repeat="ad in ad_accounts track by $index" ng-click="item_clicked($index)">
                        <span>
                            <i class="fb_icon fb_picture_wrap" ng-if="!!ad.picture['url']"><img ng-src="{{ad.picture['url']}}"/></i>
                            <i class="fb_icon fb_icon_ad" ng-if="!ad.picture['url']"></i>
                            <span class="fb_value">{{ad.name}}</span>
                        </span>
                    </a>                                
                </div>
            </div>
            <div class="main_col">
                <div class="main_col_header" ng-if="!!ad_accounts.length">
                    <div class="main_icon_wrap">
                        <i class="fb_icon fb_picture_wrap" ng-if="!!current.picture['url']"><img ng-src="{{current.picture['url']}}"/></i>
                        <i class="fb_icon fb_icon_ad" ng-if="!current.picture['url']"></i>                        
                    </div>
                    <div class="main_info">
                        <div class="main_title">{{current.name}}</div>
                        <div class="main_info_body">                            
                            <ul class="info_list"><li></li><li style="padding-left: 0px;"><a target="_blank" ng-href="{{current.link}}">{{current.link}}</a></li></ul>
                            <ul class="info_list"><li>Ad account #:</li><li>{{current.id}}</li></ul>
                            <ul class="info_list"><li>Owned by:</li><li>{{current.users['name']}}</li></ul>
                            <ul class="info_list"><li>Currency:</li><li>{{current.currency}}</li></ul>
                            <ul class="info_list"><li>Time zone:</li><li>{{current.time_zone}}</li></ul>
                        </div>
                    </div>
                </div>                
                <div class="main_col_body">
                    <table class="fb_datatable" id="ads_table">
                        <thead>
                            <tr><th style="width: 30px; text-align: left;">#</th><th>Ad ID</th></tr>
                        </thead>
                        <tbody>
                            <!--<tr ng-repeat="ad in ads track by $index">
                                <td>{{$index + 1}}</td>
                                <td>{{ad.ad_id}}</td>
                            </tr>-->
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
        