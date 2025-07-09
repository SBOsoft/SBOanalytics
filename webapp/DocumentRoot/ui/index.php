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

//UI 

define('SBO_FILE_INCLUDED_PROPERLY', true);
include('../common.php');

SBO_Authenticate();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metrics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.5.13/vue.global.prod.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js" integrity="sha512-XSmbX3mhrD2ix5fXPTRQb2FwK22sRMVQTpBP2ac8hX7Dh/605hA2QDegVWiAvZPiXIxOV0CbkmUjGionDpbCmw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="charts.js"></script>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Chart containers to ensure they have a height for ECharts to render */
        .sbo-bar-chart, .sbo-pie-chart, .sbo-sline-chart {
            min-height: 350px; /* Minimum height to prevent collapse */
            width: 100%;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        .sbo-nav{
            background-color: #3c096c;
        }
    </style>
</head>
<body>
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
        <div class="text-center">
            <form action="" class="form form-sm">
                <div class="row align-items-center">
                    <div class="col-auto">
                    Domain:
                    </div>
                    <div class="col-auto">
                        <select name="selectedDomain" id="selectedDomainSelect" v-model="domainId" class="form-select">
                            <option v-for="(row) in allDomains" v-bind:value="row.domainId">{{ row.domainName}}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                    Period:
                    </div>
                    <div class="col-3 col-md-2 col-xxl-1">
                    <input type="text" id="twStartInput" v-bind:value="twStartStr" class="form-control">
                    </div>
                    <div class="col-auto">
                    to 
                    </div>
                    <div class="col-3 col-md-2 col-xxl-1">
                    <input type="text" id="twEndInput" v-bind:value="twEndStr" class="form-control">
                    </div>
                    <div class="col-auto">
                    Group by: 
                    </div>
                    <div class="col-auto">
                        <select name="groupBy" id="groupBySelect" v-model="groupBy" class="form-select">
                            <option value="">None</option>
                            <option value="hour">Hour</option>
                            <option value="day">Day</option>
                            <option value="month">month</option>

                        </select>
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
                    <sbo-details-metrics ref="statusCodesDetailedMetrics" v-bind:show-key-value="false" elem-id="statusCodesDetailedMetrics"></sbo-details-metrics>
                </div>
                <div class="col-md-6 col-lg-6">
                    <sbo-piechart ref="httpMethodsPieChart" target-element-id="httpMethodsPieChart" title="Http methods"  series-name="Http methods"></sbo-piechart>                    
                    <sbo-details-metrics ref="httpMethodsDetailedMetrics" v-bind:show-key-value="false" elem-id="httpMethodsDetailedMetrics"></sbo-details-metrics>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="referersPieChart" target-element-id="referersPieChart" title="Referers"  series-name="Referers" v-bind:hide-legends="true"></sbo-piechart>                    
                </div>
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="pathsPieChart" target-element-id="pathsPieChart" title="Paths"  series-name="Paths" v-bind:hide-legends="true"></sbo-piechart>                    
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="uaFamilyPieChart" target-element-id="uaFamilyPieChart" title="User agents"  series-name="User agents" v-bind:hide-legends="true"></sbo-piechart>
                </div>
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="osFamilyPieChart" target-element-id="osFamilyPieChart" title="Operating systems"  series-name="Operating systems" v-bind:hide-legends="true"></sbo-piechart>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="deviceTypePieChart" target-element-id="deviceTypePieChart" title="Device types"  series-name="Device types" v-bind:hide-legends="true"></sbo-piechart>
                </div>                
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="isHumanPieChart" target-element-id="isHumanPieChart" title="Client types"  series-name="Client types" v-bind:hide-legends="false"></sbo-piechart>
                </div>
            </div>
            <div class="row mt-2">                
                <div class="col-md-8 col-lg-6">
                    <sbo-piechart ref="requestIntentPieChart" target-element-id="requestIntentPieChart" title="Request intents"  series-name="Request intents" v-bind:hide-legends="false"></sbo-piechart>
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
                    
                    twStart:202505010000,
                    twEnd: 202506010000,
                    allDomains:[],
                    domainId: 1,    //TODO fix
                    error: null,             // Error message if data fetching fails
                    resizeTimer: null        // Timer for debouncing window resize
                };
            },
            // Lifecycle hook: called after the component is mounted to the DOM
            mounted() {
                this.loadAllDomains();
                
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

                // Add a global event listener for window resize to make charts responsive
                // We debounce the resize event to prevent excessive re-rendering
                window.addEventListener('resize', this.resizeCharts);
            },
            // Lifecycle hook: called before the component is unmounted from the DOM
            beforeUnmount() {
                // Clean up the resize event listener to prevent memory leaks
                window.removeEventListener('resize', this.resizeCharts);
            },
            computed:{
                twStartStr(){
                    return 'TW START';
                },
                twEndStr(){
                    return 'TW END';
                }
            },
            // Methods for the component
            methods: {
                
                loadAllDomains(){
                    var self = this;
                    window.fetch('../api/domains').then((response)=>{
                        response.json().then((parsedJson)=>{
                            self.allDomains = parsedJson;
                        });
                    });
                },
                chartClicked(chartType, chartId, clickParams){
                    console.log(clickParams);
                    switch(chartId){
                        case 'statusCodesPieChart':
                            this.$refs.statusCodesDetailedMetrics.initParams(this.domainId, 3, clickParams.name, this.twStart, this.twEnd, 20, 'Status code:' + clickParams.name);
                            this.$refs.statusCodesDetailedMetrics.goToPage(1);
                            break;
                        case 'httpMethodsPieChart':
                            this.$refs.httpMethodsDetailedMetrics.initParams(this.domainId, 5, clickParams.name, this.twStart, this.twEnd, 20, 'Method:' + clickParams.name);
                            this.$refs.httpMethodsDetailedMetrics.goToPage(1);
                            
                            break;
                        case 'referersPieChart':
                            this.detailedLogsTitle='Referer:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(6, clickParams.name);
                            break;
                        case 'pathsPieChart':
                            this.detailedLogsTitle='Path:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(7, clickParams.name);
                            break;
                        case 'uaFamilyPieChart':
                            this.detailedLogsTitle='UA:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(11, clickParams.name);
                            break;
                        case 'osFamilyPieChart':
                            this.detailedLogsTitle='OS:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(12, clickParams.name);
                            break;
                        case 'deviceTypePieChart':
                            this.detailedLogsTitle='Device:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(13, clickParams.name);
                            break;
                        case 'isHumanPieChart':
                            this.detailedLogsTitle='Human:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(14, clickParams.name);
                            break;
                        case 'requestIntentPieChart':
                            this.detailedLogsTitle='Intent:' + clickParams.name;
                            this.detailedViewShowKeyValue = false;
                            this.loadDetailedMetrics(15, clickParams.name);
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
                    var url = '../api/metrics?domainId='+this.domainId+'&metricType='+metricType+'&twStart=' + this.twStart + '&twEnd=' + this.twEnd;
                    window.fetch(url).then((response)=>{                        
                        response.json().then((parsedJson)=>{
                        console.log("parsedJson in loadTotalRequestsDataForBarChart");    
                        console.log(parsedJson);
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
                        console.log("parsedJson in loadDataGroupedByKey");    
                        console.log(parsedJson);
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

                

                

                /**
                 * Resizes all ECharts instances.
                 * This method is debounced to prevent performance issues on rapid window resizing.
                 */
                resizeCharts() {
                    clearTimeout(this.resizeTimer); // Clear previous timer
                    this.resizeTimer = setTimeout(() => {
                        // Only resize if the chart instance exists
                        if (this.barChart) this.barChart.resize();
                        if (this.pieChart) this.pieChart.resize();
                        if (this.stackedLineChart) this.stackedLineChart.resize();
                    }, 200); // Wait 200ms after the last resize event
                }
            }
        });

        app.component('sbo-barchart', SBOBarChart);
        app.component('sbo-piechart', SBOPieChart);
        app.component('sbo-linechart', SBOLineChart);
        app.component('sbo-details-metrics', SBODetailedMetricsView);
        
        
        // Mount the Vue application to the DOM element with id="app"
        app.mount('#app');
    </script>
</body>
</html>