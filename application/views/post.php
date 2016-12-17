<?php
    global $NUM_PRODUCTS_LOADING;    
?>
<script src="<?php echo base_url() . 'assets/plugins/jquery.blockUI/jquery.blockUI.min.js'; ?>"></script>
<script>
    var server = "<?php echo base_url() . "MY_Facebook"; ?>";
    var config = {
        headers: {
            'Accept': 'application/json;odata=verbose'
        }
    };
    
    angular.module('PostModule', [])
    .controller('PostController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {        
        $scope.products = <?php echo json_encode($products); ?>;        
        $scope.projects = <?php echo json_encode($projects); ?>;
        $scope.project_id = <?php echo getSelectedProjectId(); ?>;
        $scope.selected_project = $scope.projects[$scope.project_id - 1];
        
        $scope.end_of_product = 0;
        $scope.pos = 0;        
        $scope.publishing = 0;
        
        if ($scope.products != null)
            $scope.current = $scope.products[$scope.pos];                    
        
        console.log($scope.products);
        
        
        $scope.item_clicked = function (n) {
            $scope.pos = n;
            $scope.current = $scope.products[n];
        }
        
        $scope.publish = function(pid) {
            $scope.publishing = 1;
            if (pid != 0) {
                var data = {
                    products: $scope.products                    
                };
                fb_block(".main");
                $http.post(server + "/publish_weekly_products/", data)
                    .success(function (response) {
                        fb_unblock(".main");
                        console.log(response);    
                        $scope.publishing = 0;
                        time = response['time_taken'];
                        min = Math.floor(time / 60);
                        sec = time % 60;
                        alert("Done! Total time: " + min + " minutes  " + sec + " seconds");
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });    
            } else {
                alert("Please select a page.");
            }
        }        
        
        $scope.select_project = function() {            
            $scope.project_id = $scope.selected_project.projectId;
            $http.get(server + "/select_project/" + $scope.project_id)
                .success(function (response) {
                    $scope.products = response['products'];
                    if ($scope.products != null) {
                        $scope.pos = 0;
                        $scope.current = $scope.products[$scope.pos];
                    }
                    console.log(response['products']);    
                })
                .error(function (data, status, headers, config) {
                    alert(JSON.stringify(data));
                });
        }
        
        $scope.load_more = function(since_id) {
            $scope.loading = 1;
            $http.get(server + "/get_products/" + since_id + "/<?php echo NUM_PRODUCTS_LOADING; ?>", config)
            .success(function (response) {
                
                if (response.length > 0) {
                    $scope.last_product_id = response.slice(-1)[0]['id'];                
                    
                    //attach_small_image(response);
                    $scope.products = $scope.products.concat(response);
                    console.log($scope.products);
                    
                    $timeout(function () {                    
                        $('#sidebar_col_content').animate({
                           scrollTop: $('#sidebar_col_content')[0].scrollHeight                       
                        }, 300);
                    }, 200);
                } else {
                    $scope.end_of_product = 1;    
                }

                $scope.loading = 0;
            })
            .error(function (data, status, headers, config) {
                alert(JSON.stringify(data));
            });    
        }
        
        function attach_small_image(products) {            
            
            for (var k in products) {
                if (typeof products[k]['image']['src'] != 'undefined' || products[k]['image']['src'] != '') {
                    str = products[k]['image']['src'];
                    last_slash_pos = str.lastIndexOf('/');
                    last_dot_pos = str.lastIndexOf('.');
                    
                    filename = str.substring(last_slash_pos + 1, last_dot_pos);
                    products[k]['image']['small_image'] = str.substring(0, last_slash_pos + 1) + filename + '_small' + str.substring(last_dot_pos);                    
                }
            }            
        }
        
            
    }]);    
      
</script>

<div class="right" ng-app="PostModule" ng-controller="PostController">
    <div class="right_content">
        <div class="right_header">            
            <div class="left_side">
                <span class="title">Post</span>
            </div>
            <div class="right_side">
                <span class="dropdown_wrap">
                    <label>Select a project:</label>
                    <select class="select" id="projects_dropdown" ng-model="selected_project" ng-options="project as project.name for project in projects track by project.projectId" ng-change="select_project()">
                    </select>
                </span>
            </div>            
        </div>        
        <div>
            <div class="main_section clearfix">
                <div class="sidebar_col">
                    <div class="sidebar_col_content" id="sidebar_col_content">                                
                        <a class="item" ng-class="{'selected' : $index == pos}" ng-repeat="product in products track by $index" ng-click="item_clicked($index)">
                            <span>
                                <i class="fb_icon fb_picture_wrap" ng-if="!!product.small_image"><img ng-src="{{product.small_image}}"/></i>
                                <i class="fb_icon fb_icon_facebook" ng-if="!product.small_image"></i>
                                <span class="fb_value">{{product.title}}</span>
                            </span>
                        </a>
                        <!--<a ng-click="load_more(last_product_id)" id="load_more" ng-if="end_of_product != 1">
                            <span class="label">Load More</span>
                            <span class="fb_spin_wrap"><span class="fb_spin" ng-show="loading"><i class="fb_icon fb_icon_animate_spin"></i></span></span>
                        </a>
                        <p class="end_of_product" ng-if="end_of_product == 1">No More Products</p>-->
                    </div>
                </div>
                <div class="main_col" ng-if="!products.length"><div class="main_col_header clearfix">No products to submit.</div></div>
                <div class="main_col" ng-if="!!products.length">
                    <div class="main_col_header clearfix">
                        <div class="main_icon_wrap">
                            <i class="fb_icon fb_picture_wrap" ng-if="!!current.small_image"><img ng-src="{{current.small_image}}"/></i>
                            <i class="fb_icon fb_icon_page" ng-if="!current.small_image"></i>                        
                        </div>
                        <div class="main_info">
                            <div class="main_title">{{current.title}}</div>
                            <div class="main_info_body">                            
                                <ul class="info_list link"><li><a target="_blank" ng-href="{{current.link}}">{{current.link}}</a></li></ul>
                                <ul class="info_list"><li>Product #</li><li>{{current.shopifyId}}</li></ul>
                                <ul class="info_list"><li>Created At</li><li>{{current.published_at}}</li></ul>
                            </div>
                        </div>
                    </div>
                    <div class="main_col_body">
                        <div class="fb_row">
                            <div class="form_label">Facebook Pages</div>
                            <div class="form_input">
                                <ul class="fb_links">
                                    <li ng-repeat="page in current.pages track by $index"><a href="{{page.url}}" target="_blank">{{page.name}}</a></li> 
                                </ul>
                            </div>
                        </div>
                        <div class="fb_row">
                            <div class="form_label">Collections</div>
                            <div class="form_input">                            
                                <span>{{current.collections}}</span>
                            </div>
                        </div>
                        <div class="fb_row">
                            <div class="form_label">Content</div>
                            <div class="form_input">
                                <textarea class="content_input" ng-model="current.content">{{current.content}}</textarea>                            
                            </div>
                        </div>
                        <div class="fb_row">
                            <div class="form_label">Link</div>
                            <div class="form_input">
                                <input type="text" ng-model="current.link" name="product_link"/>
                            </div>
                        </div>
                        <div class="fb_row">
                            <div class="form_label">Image</div>
                            <div class="form_input">
                                <img ng-src="{{current.image}}" class="product_image"/>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class="right_bottom clearfix">
                <span class="left_side"><span class="total_products">{{products.length}} Products</span></span>
                <span class="right_side">
                    <span class="fb_spin_wrap publishing"><span class="fb_spin" ng-show="publishing"><i class="fb_icon fb_icon_animate_spin"></i></span></span>                    
                    <a class="btn" ng-class="{'disabled' : page_id == 0 || publishing == 1}" ng-click="publish(page_id)">{{ publishing === 0 ? "Publish" : "Publishing Now"}}</a>
                </span>    
            </div>
        </div>
    </div>
</div>
        