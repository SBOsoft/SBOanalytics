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
        <div class="text-center px-4 py-2 border-bottom bg-primary-subtle">
            <form action="" class="form form-sm" onsubmit="return false;">
                <div class="row align-items-center">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-auto my-1 row">
                        <label class="col-3 col-md-auto col-form-label">Host</label>
                        <div class="col-auto">
                            <select name="selectedDomain" id="selectedHostSelect" v-model="hostId" class="form-select">
                                <option v-for="(row) in allHosts" v-bind:value="row.hostId">{{ row.hostId }} - {{ row.hostName }}</option>
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
                        <button class="btn btn-primary" v-on:click="loadCharts" type="button">Show Metrics</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="container-fluid py-3">

            <div v-if="error" class="alert alert-danger text-center mx-auto" style="max-width: 600px;" role="alert">
                {{ error }}
            </div>
            <div v-if="hasMoreData" class="alert alert-warning">
                Please narrow down your search criteria, only the first {{ dataLimit }} results are shown but there are more
            </div>

            <div class="row">
                <div class="col-md-6 col-lg-6">                    
                    <sbo-linechart ref="loadAveragesChart" v-bind:no-border="false" target-element-id="loadAveragesChart" title="Load averages" y-axis-name="Load" series-name="Load average"></sbo-linechart>
                    <div class="my-2" v-if="loadAveragesDataForTable && loadAveragesDataForTable.length>0">
                        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#loadAveragesDataTable" aria-expanded="false" aria-controls="loadAveragesDataTable" title="Show chart data in tabular format">
                            Chart data
                        </button>
                        <div v-if="loadAveragesDataForTable && loadAveragesDataForTable.length>0" class="mt-2 collapse d-lg-block" id="loadAveragesDataTable">
                            <h6 class="text-primary-emphasis">Uptime<sup>*</sup></h6>
                            <table class="table table-sm table-hover small">
                                <thead>
                                    <tr>
                                        <td class="text-end">Date/time</td>
                                        <td class="text-end">1 min. avg.</td>
                                        <td class="text-end">5 min. avg.</td>
                                        <td class="text-end">15 min. avg.</td>
                                        <td class="text-end">Uptime (minutes)</td>
                                        <td class="text-end">Users</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row) in loadAveragesDataForTable">
                                        <td class="text-end pe-3">{{ row.label}}</td>
                                        <td class="text-end pe-3">{{ row.loadAverage1}}</td>
                                        <td class="text-end pe-3">{{ row.loadAverage5}}</td>
                                        <td class="text-end pe-3">{{ row.loadAverage15}}</td>
                                        <td class="text-end pe-3" v-bind:title="row.hostUptimeMinutes + ' minutes'">{{ formatMinutes(row.hostUptimeMinutes) }}</td>
                                        <td class="text-end pe-3">{{ row.loggedInUsers}}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="small text-secondary">
                                <sup>*</sup> Same data as the chart displayed in tabular format.
                                Same as <code>uptime</code> command.
                                When group by is not None, averages of saved metrics for each period will be displayed.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-6">                    
                    <sbo-linechart ref="memoryUsagesChart"  target-element-id="memoryUsagesChart" title="Memory and swap use" y-axis-name="Bytes" series-name="Memory" value-format="bytes"></sbo-linechart>                    
                    <div class="my-2" v-if="memoryUsagesDataForTable && memoryUsagesDataForTable.length>0">                        
                        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#memoryUsagesDataTable" aria-expanded="false" aria-controls="memoryUsagesDataTable" title="Show chart data in tabular format">
                            Chart data
                        </button>
                        <div v-if="memoryUsagesDataForTable && memoryUsagesDataForTable.length>0" class="mt-2 collapse d-lg-block" id="memoryUsagesDataTable">
                            <h6 class="text-primary-emphasis">Memory data <sup>**</sup></h6>
                            <table class="table table-sm table-hover small">
                                <thead>
                                    <tr>
                                        <td class="text-end">Date/time</td>
                                        <td class="text-end">Memory used</td>
                                        <td class="text-end">Free memory</td>
                                        <td class="text-end">Available memory</td>
                                        <td class="text-end">Swap used</td>
                                        <td class="text-end">Cache used</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row) in memoryUsagesDataForTable">
                                        <td class="text-end pe-3">{{ row.label}}</td>
                                        <td class="text-end pe-3" v-bind:title="row.memoryUsed + ' bytes'">{{ formatBytes(row.memoryUsed) }}</td>
                                        <td class="text-end pe-3" v-bind:title="row.memoryFree + ' bytes'">{{ formatBytes(row.memoryFree) }}</td>
                                        <td class="text-end pe-3" v-bind:title="row.memoryAvailable + ' bytes'">{{ formatBytes(row.memoryAvailable) }}</td>
                                        <td class="text-end pe-3" v-bind:title="row.swapUsed + ' bytes'">{{ formatBytes(row.swapUsed) }}</td>
                                        <td class="text-end pe-3" v-bind:title="row.cacheUsed + ' bytes'">{{ formatBytes(row.cacheUsed) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="small text-secondary">
                                <sup>**</sup> Same data as the chart displayed in tabular format.
                                Same as <code>/proc/meminfo</code> or <code>free</code> command.
                                When group by is not None, averages of saved metrics for each period will be displayed.
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
                        
        </div>        
    </div>

    <script>
        const app = Vue.createApp({
            components:{
                SBOLineChart
            },
            // Data properties for the component
            data() {
                return {                    
                    allHosts:{},
                    hostId: 0,
                    //true when the page was opened with filters in the URL, charts load automatically then
                    autoLoad: false,
                    error: 'Select host and period and click Show Metrics to start',
                    twStartStr:'<?php 
                        $yesterday = strtotime("-1 day");
                        echo date('Y-m-d', $yesterday).'T00:00';
                    ?>',
                    twEndStr:'<?php echo date('Y-m-d').'T'.date('H:i');?>',
                    groupBy:'hour',
                    hasMoreData:false,
                    dataLimit:100,
                    loadAveragesDataForTable:[],
                    memoryUsagesDataForTable:[]
                };
            },
            mounted() {
            },            
            beforeMount() {
                this.applyUrlState();
                this.loadAllHosts();
            },

            // Methods for the component
            methods: {
                //filters from the URL (bookmarked/shared/refreshed page), see SBO_ReadUrlState
                applyUrlState(){
                    const state = SBO_ReadUrlState({
                        hostId: SBO_IsPositiveIntString,
                        twStart: SBO_IsDateTimeLocalString,
                        twEnd: SBO_IsDateTimeLocalString,
                        groupBy: SBO_IsGroupByString
                    });
                    if(state.hostId){
                        this.hostId = parseInt(state.hostId);
                        this.autoLoad = true;
                    }
                    if(state.twStart){
                        this.twStartStr = state.twStart;
                    }
                    if(state.twEnd){
                        this.twEndStr = state.twEnd;
                    }
                    if(state.groupBy !== undefined){
                        this.groupBy = state.groupBy;
                    }
                },
                loadCharts(){
                    if(!this.hostId){
                        this.error = 'Select a host first';
                        return;
                    }
                    this.error = '';
                    SBO_WriteUrlState({
                        hostId: this.hostId,
                        twStart: this.twStartStr,
                        twEnd: this.twEndStr,
                        groupBy: this.groupBy
                    });
                    this.loadDataForCharts();
                },
                loadAllHosts(){
                    var self = this;
                    SBO_FetchList('../api/hosts', 'hostId').then((hosts)=>{
                        self.allHosts = hosts;
                        const hostIds = Object.keys(hosts);
                        if(hostIds.length === 0){
                            self.error = 'No hosts found. Add a host on the settings page first.';
                            return;
                        }
                        //keep the host from the URL when it exists, otherwise preselect the first one
                        if(!hosts[self.hostId]){
                            self.hostId = parseInt(hostIds[0]);
                            self.autoLoad = false;
                        }
                        if(self.autoLoad){
                            self.loadCharts();
                        }
                    }).catch((err)=>{
                        self.error = 'Could not load hosts: ' + err.message;
                    });
                },
                formatBytes(value){
                    return SBO_FormatBytes(value);
                },
                formatMinutes(value){
                    return SBO_FormatMinutes(value);
                },
                /*
                 Chart/table label of an os-metrics row: raw rows have metricTS, grouped rows have
                 day+hour, day, or year+month (hour and month can be 0/small numbers, don't test truthiness).
                 */
                rowLabel(row){
                    if(row.metricTS){
                        return row.metricTS;
                    }
                    const pad2 = (n)=>String(n).padStart(2, '0');
                    if(row.hour !== undefined && row.hour !== null){
                        return row.day + ' ' + pad2(row.hour) + ':00';
                    }
                    if(row.day){
                        return row.day;
                    }
                    if(row.month !== undefined && row.month !== null){
                        return row.year + '-' + pad2(row.month);
                    }
                    return '';
                },
                loadDataForCharts(){
                    var self = this;
                    this.hasMoreData = false;
                    this.loadAveragesDataForTable = [];
                    this.memoryUsagesDataForTable = [];
                    var url = '../api/os-metrics?hostId='+encodeURIComponent(this.hostId)+
                            '&twStart=' + encodeURIComponent(this.twStartStr.replace(/T/, ' ')) + 
                            '&twEnd=' + encodeURIComponent(this.twEndStr.replace(/T/, ' ')) + 
                            '&groupBy=' + encodeURIComponent(this.groupBy) + 
                            '&limit=' + encodeURIComponent(this.dataLimit);
                    SBO_FetchJson(url).then((parsedJson)=>{
                        self.hasMoreData = parsedJson.hasMoreResults;
                        let loadAveragesChartData = {
                            '1 minute load average':[],
                            '5 minute load average': [],
                            '15 minute load average': []
                        };
                        let memoryUsagesChartData = {
                            'SWAP used':[],
                            'Cache used':[],
                            'Memory used':[],
                            'Free memory':[],
                            'Available memory':[]
                        };
                        let prevLabelPrefix = '';
                        for(let rowIndex in parsedJson.data){
                            if(!parsedJson.data[rowIndex]){
                                continue;
                            }
                            let labelValue = self.rowLabel(parsedJson.data[rowIndex]);
                            //same day as the previous row: show only the time part
                            let splitLabel = labelValue.split(' ');
                            if(splitLabel[0]!==prevLabelPrefix){
                                prevLabelPrefix = splitLabel[0];
                            }
                            else{
                                labelValue = splitLabel[1];
                            }
                            loadAveragesChartData['1 minute load average'].push({
                                value:parsedJson.data[rowIndex].loadAverage1,
                                name:labelValue
                            });
                            loadAveragesChartData['5 minute load average'].push({
                                value:parsedJson.data[rowIndex].loadAverage5,
                                name:labelValue
                            });
                            loadAveragesChartData['15 minute load average'].push({
                                value:parsedJson.data[rowIndex].loadAverage15,
                                name:labelValue
                            });
                            self.loadAveragesDataForTable.push({
                                label:labelValue,
                                loadAverage1:parsedJson.data[rowIndex].loadAverage1,
                                loadAverage5: parsedJson.data[rowIndex].loadAverage5,
                                loadAverage15: parsedJson.data[rowIndex].loadAverage15,
                                hostUptimeMinutes:parsedJson.data[rowIndex].hostUptimeMinutes,
                                loggedInUsers:parsedJson.data[rowIndex].loggedInUsers
                            });
                            memoryUsagesChartData['SWAP used'].push({
                                value:parsedJson.data[rowIndex].swapUsed,
                                name:labelValue
                            });
                            memoryUsagesChartData['Cache used'].push({
                                value:parsedJson.data[rowIndex].cacheUsed,
                                name:labelValue
                            });
                            memoryUsagesChartData['Memory used'].push({
                                value:parsedJson.data[rowIndex].memoryUsed,
                                name:labelValue
                            });
                            memoryUsagesChartData['Free memory'].push({
                                value:parsedJson.data[rowIndex].memoryFree,
                                name:labelValue
                            });
                            memoryUsagesChartData['Available memory'].push({
                                value:parsedJson.data[rowIndex].memoryAvailable,
                                name:labelValue
                            });
                            
                            self.memoryUsagesDataForTable.push({
                                label:labelValue,
                                swapUsed: parsedJson.data[rowIndex].swapUsed,
                                cacheUsed: parsedJson.data[rowIndex].cacheUsed,
                                memoryUsed: parsedJson.data[rowIndex].memoryUsed,
                                memoryAvailable: parsedJson.data[rowIndex].memoryAvailable,
                                memoryFree: parsedJson.data[rowIndex].memoryFree
                            });
                        }
                        self.$refs['loadAveragesChart'].showChart(loadAveragesChartData);
                        self.$refs['memoryUsagesChart'].showChart(memoryUsagesChartData);
                    }).catch((err)=>{
                        self.$refs['loadAveragesChart'].showError(err.message);
                        self.$refs['memoryUsagesChart'].showError(err.message);
                    });
                },
            }
        });

        app.component('sbo-linechart', SBOLineChart);

        app.mount('#app');
    </script>
