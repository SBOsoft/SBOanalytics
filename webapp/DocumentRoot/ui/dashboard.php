<?php
/*
Copyright (C) 2025 SBOSOFT, Serkan Özkan

This file is part of, SBOanalytics web site analytics 

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
if(!defined('SBO_FILE_INCLUDED_PROPERLY')){
    http_response_code(404);
    exit;
}
?>
    <div id="app">
        <nav class="navbar navbar-expand-lg sbo-nav shadow-sm" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">SBO Analytics</a>                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="https://github.com/SBOsoft/SBOanalytics" title="SBOanalytics github repository">
                                <i class="bi bi-github"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://www.sbosoft.net">SBOSOFT</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="text-center px-4 py-2 border-bottom bg-primary-subtle">
            <form action="" class="form form-sm" onsubmit="return false;">
                <div class="row align-items-center">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-auto my-1 row">
                        <label class="col-3 col-md-auto col-form-label">Domain</label>
                        <div class="col-auto">
                            <select name="selectedDomain" id="selectedDomainSelect" v-model="domainId" class="form-select">
                                <option v-for="(row) in allDomains" v-bind:value="row.domainId">{{ row.domainName}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-auto my-1 row">
                        <label class="col-3 col-md-auto col-form-label">Between</label>
                        <div class="col-auto">
                            <input type="datetime-local" id="twStartInput" v-model="twStartStr" class="form-control">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-auto my-1 row">
                        <label class="col-3 col-md-auto col-form-label">and</label>
                        <div class="col-auto">
                            <input type="datetime-local" id="twEndInput" v-model="twEndStr" class="form-control">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-lg-auto row my-1">
                        <label class="col-3 col-md-auto col-form-label">Group by</label>
                        <div class="col-auto">
                            <select name="groupBy" id="groupBySelect" v-model="groupBy" class="form-select">
                                <option value="">None</option>
                                <option value="hour">Hour</option>
                                <option value="day">Day</option>
                                <option value="month">Month</option>

                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-auto my-1 row">
                        <div class="col-auto">
                        <button class="btn btn-primary" v-on:click="loadCharts" type="button">Show Analytics</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="container-fluid py-5">

            <div v-if="error" class="alert alert-danger text-center mx-auto" style="max-width: 600px;" role="alert">
                {{ error }}
            </div>

            <div class="row">
                <div class="col-md-6 col-lg-6">                    
                    <sbo-barchart ref="totalRequestsBarChart" bar-color="" hover-color="" target-element-id="totalRequestsBarChart" title="Total requests" y-axis-name="Req. Count" series-name="Requests"></sbo-barchart>                    
                </div>
                <div class="col-md-6 col-lg-6">
                    <sbo-barchart ref="bytesSentBarChart" bar-color="#0a9396" hover-color="#219ebc" target-element-id="bytesSentBarChart" title="Bytes sent" y-axis-name="Bytes" series-name="Bytes sent"></sbo-barchart>                    
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="statusCodesPieChart" target-element-id="statusCodesPieChart" title="Response status codes"  series-name="Status codes"></sbo-piechart>                    
                    <sbo-details-metrics ref="statusCodesDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="statusCodesDetailedMetrics"></sbo-details-metrics>
                </div>
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="httpMethodsPieChart" target-element-id="httpMethodsPieChart" title="Http methods"  series-name="Http methods"></sbo-piechart>                    
                    <sbo-details-metrics ref="httpMethodsDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="httpMethodsDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="referersPieChart" target-element-id="referersPieChart" title="Top referers"  series-name="Referers" v-bind:hide-legends="true"></sbo-piechart>                    
                    <sbo-details-metrics ref="referersDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="referersDetailedMetrics"></sbo-details-metrics>
                </div>
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="pathsPieChart" target-element-id="pathsPieChart" title="Top paths"  series-name="Paths" v-bind:hide-legends="true"></sbo-piechart>                    
                    <sbo-details-metrics ref="pathsDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="pathsDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="uaFamilyPieChart" target-element-id="uaFamilyPieChart" title="User agents"  series-name="User agents" v-bind:hide-legends="true"></sbo-piechart>
                    <sbo-details-metrics ref="uaFamilyDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="uaFamilyDetailedMetrics"></sbo-details-metrics>
                </div>
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="osFamilyPieChart" target-element-id="osFamilyPieChart" title="Operating systems"  series-name="Operating systems" v-bind:hide-legends="true"></sbo-piechart>
                    <sbo-details-metrics ref="osFamilyDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="osFamilyDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="deviceTypePieChart" target-element-id="deviceTypePieChart" title="Device types"  series-name="Device types" v-bind:hide-legends="true"></sbo-piechart>
                    <sbo-details-metrics ref="deviceTypesDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="deviceTypesDetailedMetrics"></sbo-details-metrics>
                </div>                
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="isHumanPieChart" target-element-id="isHumanPieChart" title="Client types"  series-name="Client types" v-bind:hide-legends="false"></sbo-piechart>
                    <sbo-details-metrics ref="isHumanDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="isHumanDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="requestIntentPieChart" target-element-id="requestIntentPieChart" title="Request intents"  series-name="Request intents" v-bind:hide-legends="false"></sbo-piechart>
                    <sbo-details-metrics ref="intentsDetailedMetrics" v-bind:show-key-value-prop="false" elem-id="intentsDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>            
        </div>        
    </div>

    <script>
        const app = Vue.createApp({
            components:{
                SBOBarChart,
                SBOPieChart,
                SBODetailedMetricsView,
                SBOLineChart
            },
            // Data properties for the component
            data() {
                return {                    
                    allDomains:[],
                    domainId: 0,
                    error: 'Select domain and period and click Show Analytics to start',
                    twStartStr:'<?php 
                        $lastWeek = strtotime("-1 week");
                        echo date('Y-m-d', $lastWeek).'T00:00';
                    ?>',
                    twEndStr:'<?php echo date('Y-m-d').'T'.date('h:i');?>',
                    groupBy:'day'
                };
            },
            mounted() {
            },            
            beforeMount() {
                this.loadAllDomains();
            },
            computed:{                
                twStart(){
                    return this.twStartStr.replace(/[^0-9]+/, '');
                },
                twEnd(){
                    return this.twEndStr.replace(/[^0-9]+/, '');
                }
            },
            // Methods for the component
            methods: {
                loadCharts(){
                    this.error = '';
                    this.loadTotalRequestsData();
                    this.loadBytesSentData();
                    this.loadRequestsByStatusCodesData();
                    this.loadRequestsByHttpMethodsData();

                    this.loadRequestsByReferersData();
                    this.loadRequestsByPathsData();

                    this.loadUAFamiliesData();
                    this.loadOSFamiliesData();

                    this.loadDeviceTypesData();

                    this.loadIsHumanData();
                    this.loadRequestIntentsData();  
                },
                loadAllDomains(){
                    var self = this;
                    window.fetch('../api/domains').then((response)=>{
                        response.json().then((parsedJson)=>{
                            self.allDomains = parsedJson;
                        });
                    });
                },
                chartClicked(chartType, chartId, clickParams){
                    switch(chartId){
                        case 'statusCodesPieChart':
                            this.$refs.statusCodesDetailedMetrics.initParams(this.domainId, 3, clickParams.name, this.twStart, this.twEnd, 20, 'Status code:' + clickParams.name);
                            this.$refs.statusCodesDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'httpMethodsPieChart':
                            this.$refs.httpMethodsDetailedMetrics.initParams(this.domainId, 5, clickParams.name, this.twStart, this.twEnd, 20, 'Method:' + clickParams.name);
                            this.$refs.httpMethodsDetailedMetrics.goToPage(1, this.groupBy);
                            
                            break;
                        case 'referersPieChart':                            
                            this.$refs.referersDetailedMetrics.initParams(this.domainId, 6, clickParams.name, this.twStart, this.twEnd, 20, 'Referer:' + clickParams.name);
                            this.$refs.referersDetailedMetrics.goToPage(1, this.groupBy);                            
                            break;
                        case 'pathsPieChart':                            
                            this.$refs.pathsDetailedMetrics.initParams(this.domainId, 7, clickParams.name, this.twStart, this.twEnd, 20, 'Path:' + clickParams.name);
                            this.$refs.pathsDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'uaFamilyPieChart':
                            this.$refs.uaFamilyDetailedMetrics.initParams(this.domainId, 11, clickParams.name, this.twStart, this.twEnd, 20, 'User agent:' + clickParams.name);
                            this.$refs.uaFamilyDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'osFamilyPieChart':
                            this.$refs.osFamilyDetailedMetrics.initParams(this.domainId, 12, clickParams.name, this.twStart, this.twEnd, 20, 'OS:' + clickParams.name);
                            this.$refs.osFamilyDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'deviceTypePieChart':
                            this.$refs.deviceTypesDetailedMetrics.initParams(this.domainId, 13, clickParams.name, this.twStart, this.twEnd, 20, 'Device:' + clickParams.name);
                            this.$refs.deviceTypesDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'isHumanPieChart':
                            this.$refs.isHumanDetailedMetrics.initParams(this.domainId, 14, clickParams.name, this.twStart, this.twEnd, 20, 'Human:' + clickParams.name);
                            this.$refs.isHumanDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                        case 'requestIntentPieChart':
                            this.$refs.intentsDetailedMetrics.initParams(this.domainId, 15, clickParams.name, this.twStart, this.twEnd, 20, 'Intent:' + clickParams.name);
                            this.$refs.intentsDetailedMetrics.goToPage(1, this.groupBy);
                            break;
                            
                    }
                },
                
                loadTotalRequestsData(){
                    this.loadTotalRequestsDataForBarChart(1, 'totalRequestsBarChart');
                },
                loadBytesSentData(){
                    this.loadTotalRequestsDataForBarChart(2, 'bytesSentBarChart');
                },                
                loadTotalRequestsDataForBarChart(metricType, chartId){
                    var self = this;
                    var url = '../api/metrics?domainId='+encodeURIComponent(this.domainId)+
                            '&metricType='+encodeURIComponent(metricType)+
                            '&twStart=' + encodeURIComponent(this.twStart) + 
                            '&twEnd=' + encodeURIComponent(this.twEnd) + 
                            '&groupBy=' + encodeURIComponent(this.groupBy);
                    window.fetch(url).then((response)=>{                        
                        response.json().then((parsedJson)=>{
                            let chartData = {
                                xLabels:[],
                                values:[]
                            };
                            for(let rowIndex in parsedJson.data){
                                if(!parsedJson.data[rowIndex]){
                                    continue;
                                }
                                chartData.values.push(parsedJson.data[rowIndex].metric);
                                chartData.xLabels.push(SBO_FormatTimeWindow(parsedJson.data[rowIndex].tw, parsedJson.data, rowIndex));
                            }                        
                            self.$refs[chartId].showChart(chartData);
                            self.$refs[chartId].chartObj.on('click', (clickParams)=>{
                                self.chartClicked('bar', chartId, clickParams);
                            });
                        });
                        
                    });
                },
                loadRequestsByHttpMethodsData(){
                    this.loadDataGroupedByKey(5, 'httpMethodsPieChart');
                },
                loadRequestsByStatusCodesData(){
                    this.loadDataGroupedByKey(3, 'statusCodesPieChart');
                },
                loadRequestsByReferersData(){
                    this.loadDataGroupedByKey(6, 'referersPieChart', 20);
                },
                loadRequestsByPathsData(){
                    this.loadDataGroupedByKey(7, 'pathsPieChart', 20);
                },
                loadUAFamiliesData(){
                    this.loadDataGroupedByKey(11, 'uaFamilyPieChart');
                },
                loadOSFamiliesData(){
                    this.loadDataGroupedByKey(12, 'osFamilyPieChart');
                },
                loadDeviceTypesData(){
                    this.loadDataGroupedByKey(13, 'deviceTypePieChart');
                },
                loadIsHumanData(){
                    this.loadDataGroupedByKey(14, 'isHumanPieChart');
                },
                loadRequestIntentsData(){
                    this.loadDataGroupedByKey(15, 'requestIntentPieChart');
                },
                
                
                loadDataGroupedByKey(metricType, chartId, limit){
                    var self = this;
                    var url = '../api/metrics?domainId='+encodeURIComponent(this.domainId)+'&metricType='+encodeURIComponent(metricType)+
                            '&groupBy=key&twStart=' + encodeURIComponent(this.twStart) + '&twEnd=' + encodeURIComponent(this.twEnd);
                    if(limit){
                        url+='&limit='+encodeURIComponent(limit);
                    }
                    window.fetch(url).then((response)=>{                        
                        response.json().then((parsedJson)=>{
                            let chartData = {
                                legends:[],
                                values:[]
                            };
                            for(let row of parsedJson.data){
                                if(!row){
                                    continue;
                                }
                                chartData.values.push({value:row.metric, name:row.keyValue});
                                chartData.legends.push(row.keyValue);
                            }                        
                            self.$refs[chartId].showChart(chartData);
                            self.$refs[chartId].chartObj.on('click', (clickParams)=>{
                                self.chartClicked('pie', chartId, clickParams);
                            });
                        });
                        
                    });                    
                },
            }
        });

        app.component('sbo-barchart', SBOBarChart);
        app.component('sbo-piechart', SBOPieChart);
        app.component('sbo-linechart', SBOLineChart);
        app.component('sbo-details-metrics', SBODetailedMetricsView);
        
        
        // Mount the Vue application to the DOM element with id="app"
        app.mount('#app');
    </script>
