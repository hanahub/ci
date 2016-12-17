<?php
    $method = $this->uri->segment(2);    
    $method2 = $this->uri->segment(3);
    if ($method == '' || $method == 'index') $method = 'products';    
?>

<script>

    var server = "<?php echo base_url(); ?>";
    var config = {
        headers: {
            'Accept': 'application/json;odata=verbose'
        }
    };

    //var pids = [];
    
    angular.module('ControlPanelModule', [])
    .controller('ControlPanelController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
        $scope.method = "<?php echo $method; ?>";    
        $scope.method2 = "<?php echo $method2; ?>";    
        $scope.publishing = 0;
        
        $scope.projects = <?php echo json_encode($projects); ?>;
        $scope.project_id = <?php echo getSelectedProjectId(); ?>;
        $scope.selected_project = $scope.projects[$scope.project_id - 1];
        
        $scope.select_project = function() {            
            $scope.project_id = $scope.selected_project.projectId;                        
            $http.get(server + "MY_Shopify/set_project/" + $scope.project_id)
                .success(function (response) {            
                    console.log(response);
                    location.href = server + "Control_Panel/<?php echo $method; ?>/";
                })
                .error(function (data, status, headers, config) {
                    console.log(data);
                });
        }
        
        $scope.import = function() {
            $scope.publishing = 1;
            
            var url = server + 'Control_Panel/';
            if ($scope.method == 'facebook_pages') {
                url = url + "import_pages";
            } else if ($scope.method == 'products') {
                url = url + "import_products";
            } else if ($scope.method == 'collections') {
                url = url + "import_collections";            
            } else if ($scope.method == 'page_collection_link') {
                url = url + "import_page_collection_link";
            }
            fb_block(".main");
            $http.get(url, config)
            .success(function (data) {
                fb_unblock(".main");                    
                $scope.publishing = 0;
                console.log(data);
                //location.reload();
            })
            .error(function (data, status, headers, config) {
                console.log(data);
            });
        }
        
        $scope.publish_products = function() {  
            var pids = [];
            $(".product_checkbox:checked").each(function() {
                pids.push($(this).attr("data-id"));
            });
            console.log(pids);
            if (pids.length > 0) {
                var data = {
                    pids: pids 
                };
                fb_block(".main");
                $http.post(server + "MY_Facebook/publish_products/", data)
                    .success(function (response) {                        
                        fb_unblock(".main");
                        console.log(response);
                        time = response['time_taken'];
                        min = Math.floor(time / 60);
                        sec = time % 60;
                        //alert("Done! Total time: " + min + " minutes  " + sec + " seconds");
                        alert(response['result'][0]['result']['description']);
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });    
            } else {
                alert("Please select products to launch.");
            }
        }
        
        $scope.add_to_queue = function() {
            var pids = [];
            $(".product_checkbox:checked").each(function() {
                pids.push($(this).attr("data-id"));
            });
            
            if (pids.length > 0) {
                var data = {
                    pids: pids 
                };
                fb_block(".main");
                $http.post(server + "MY_Facebook/add_to_queue/", data)
                    .success(function (response) {                        
                        fb_unblock(".main");
                        console.log(response);
                        alert("Products are added to the queue.");
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });    
            } else {
                alert("Please select products to add to queue.");
            }
        }

        $scope.create_slideshow_ad = function() {
            var pids = [];
            $(".product_checkbox:checked").each(function() {
                pids.push($(this).attr("data-id"));
            });
            
            if (pids.length > 0) {
                var data = {
                    pids: pids 
                };
                fb_block(".main");
                $http.post(server + "MY_Facebook/create_slideshow_ad/", data)
                    .success(function (response) {                        
                        fb_unblock(".main");
                        console.log(response);
                        alert(response["result"]["message"]);
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });    
            } else {
                alert("Please select products to create slideshow ad.");
            }
        }

        $scope.create_carousel_ad = function() {
            var pids = [];
            $(".product_checkbox:checked").each(function() {
                pids.push($(this).attr("data-id"));
            });
            
            if (pids.length > 0) {
                var data = {
                    pids: pids 
                };
                fb_block(".main");
                $http.post(server + "MY_Facebook/create_carousel_ad/", data)
                    .success(function (response) {
                        fb_unblock(".main");
                        console.log(response);
                        alert(response["result"]["message"]);
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });    
            } else {
                alert("Please select products to create carousel ad.");
            }
        }
    }]);
    
    jQuery(document).ready(function($) {
        $('#field-interests').tokenfield();
        $('#field-job_titles').tokenfield();
        $('#field-fields_of_study').tokenfield();
        $('#field-employers').tokenfield();
        $('#field-schools').tokenfield();
        $(".token-input").attr("placeholder", "Type something and hit enter");

        $(document).on("click", ".product_checkbox", function(e) {
            /*pid = $(this).attr("data-id");
            if ($(this).is(":checked")) {
                pids.push(pid);
            } else {
                pids = $.grep(pids, function(value) {
                    return value != pid;
                });
            }
            console.log(pids);*/
        });
    });
</script>
<div class="right" ng-app="ControlPanelModule" ng-controller="ControlPanelController">
    <div class="right_content">
        <div class="right_header">            
            <span class="left_side">
                <span class="title">Control Panel</span>                
            </span>
            <div class="right_side">                
                <span class="dropdown_wrap">
                    <label>Select a project:</label>
                    <select class="select" id="projects_dropdown" ng-model="selected_project" ng-options="project as project.name for project in projects track by project.projectId" ng-change="select_project()">
                    </select>
                </span>                
            </div>            
        </div>
        <div class="right_body">            
            <div class="main_col_header">
                <div class="table_list">
                    <a href="<?php echo base_url() . 'control_panel/products'; ?>" class="<?php if ($method == "products") echo "active"; ?>">Products</a>
                    <a href="<?php echo base_url() . 'control_panel/collections'; ?>" class="<?php if ($method == "collections") echo "active"; ?>">Collections</a>
                    <a href="<?php echo base_url() . 'control_panel/facebook_pages'; ?>" class="<?php if ($method == "facebook_pages") echo "active"; ?>">Facebook Pages</a>
                    <!--<a href="<?php echo base_url() . 'control_panel/category'; ?>" class="<?php if ($method == "category") echo "active"; ?>">Category</a>
                    <a href="<?php echo base_url() . 'control_panel/niche'; ?>" class="<?php if ($method == "niche") echo "active"; ?>">Niche</a>-->                    
                    <a href="<?php echo base_url() . 'control_panel/page_collection_link'; ?>" class="<?php if ($method == "page_collection_link") echo "active"; ?>">Page Collection Link</a>
                    <!--<a href="<?php echo base_url() . 'control_panel/project_niche_link'; ?>" class="<?php if ($method == "project_niche_link") echo "active"; ?>">Project Niche Link</a>-->
                    <a href="<?php echo base_url() . 'control_panel/projects'; ?>" class="<?php if ($method == "projects") echo "active"; ?>">Projects</a>
                    <a href="<?php echo base_url() . 'control_panel/targeting'; ?>" class="<?php if ($method == "targeting") echo "active"; ?>">Targeting</a>
                </div>                
            </div>
            <div class="main_col_body">                
                <div class="fb_row">
                    <?php echo $output; ?>                    
                </div>
                <div class="fb_row">
                    <div class="import_wrap clearfix" ng-if="(method == 'facebook_pages' || method == 'products' || method == 'collections' || method == 'page_collection_link') && method2 == ''">
                        <span class="left_side">
                            <?php if ($method == "products") : ?>
                                <a class="btn" ng-click="publish_products()">Launch Selected Products</a>
                                <a class="btn" ng-click="add_to_queue()">Add To Queue</a>
                                <a class="btn" ng-click="create_slideshow_ad()">Create Slideshow Ad</a>
                                <a class="btn" ng-click="create_carousel_ad()">Create Carousel Ad</a>
                            <?php endif; ?>                            
                        </span>
                        <span class="right_side">
                            <span class="fb_spin_wrap publishing"><span class="fb_spin" ng-show="publishing"><i class="fb_icon fb_icon_animate_spin"></i></span></span>                    
                            <a class="btn" ng-class="{'disabled' : publishing == 1}" ng-click="import()" ng-switch on="method">
                                <span ng-switch-when="products">{{ publishing === 0 ? "Import Products" : "Importing Now"}}</span>
                                <span ng-switch-when="collections">{{ publishing === 0 ? "Import Collections" : "Importing Now"}}</span>
                                <span ng-switch-when="facebook_pages">{{ publishing === 0 ? "Import FB Pages" : "Importing Now"}}</span>
                                <span ng-switch-when="page_collection_link">{{ publishing === 0 ? "Import Page Collection Link" : "Importing Now"}}</span>
                            </a>
                        </span>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
        