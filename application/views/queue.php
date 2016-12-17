<script>    
    angular.module('QueueModule', [])
        .controller('QueueController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
            var server = "<?php echo base_url(); ?>";
            var config = {
                headers: {
                    'Accept': 'application/json;odata=verbose'
                }
            };
            var table;
            
            $scope.projects = <?php echo json_encode($projects); ?>;
            $scope.project_id = <?php echo getSelectedProjectId(); ?>;
            $scope.selected_project = $scope.projects[$scope.project_id - 1];
            $scope.queue = <?php echo json_encode($queue); ?>
            
            $scope.select_project = function() {            
                $scope.project_id = $scope.selected_project.projectId;                        
                $http.get(server + "MY_Shopify/set_project/" + $scope.project_id)
                    .success(function (response) {            
                        console.log(response);
                        location.href = server + "queue/" + $scope.project_id;
                    })
                    .error(function (data, status, headers, config) {
                        console.log(data);
                    });
            }

            $.fn.dataTable.ext.errMode = 'throw';
            
        }]); 
</script>

<div class="right" ng-app="QueueModule" ng-controller="QueueController">
    <div class="right_content">
        <div class="right_header">            
            <span class="left_side">
                <span class="title">Submission Queue</span>                
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
            <div class="main_col_body">                
                <div class="fb_row">
                    <table class="dataTable" id="ads_table2">
                        <thead>
                            <tr>
                                <th class="num_col">#</th>                                
                                <th class="fb_col">Product</th>                                
                                <th class="fb_col">Published</th>
                                <th class="fb_col">Updated At</th>
                                <th class="fb_col">Created At</th>
                                <th class="fb_col">Description</th>
                            </tr>
                        </thead>
                        <tbody>                    
                            <?php foreach ($queue as $row) : ?>
                            <tr>
                                <td><?php echo $row->qid; ?></td>
                                <td><?php echo "<a target='_blank' href='{$row->prurl}/products/{$row->phandle}'>{$row->ptitle}</a>"; ?>                                
                                <td>
                                <?php 
                                    if ($row->qpublished == 1) echo "Yes";
                                    else if ($row->qpublished == 0) echo "Scheduled"; 
                                    else echo "Error";
                                ?>
                                </td>
                                <td><?php echo $row->qupdated_at; ?></td>
                                <td><?php echo $row->qcreated_at; ?></td>
                                <td><?php echo $row->qdescription; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>                        
                </div>
                <div class="fb_row">
                    
                </div>                
            </div>
            
        </div>
    </div>
</div>
        