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

const sboChartBgColors =   
                    [
                    '#b5179e',  //eflatun
                    '#4895ef',  //mavi
                    '#fbb02d',  //koyu sari
                    '#0a9396',  //yesil
                    '#7678ed',  //mor gibi
                    '#219ebc',  //petrol
                    '#e85d04',  //turuncu
                    '#ffd000',  //sari
                    //'#ee2677',  //kirmizi
                    //mat
                    '#0077b6',
                    '#f3722c', 
                     '#6a994e',
                    //'#42a5f5', 
                    '#277da1',
                     '#d4a373', //sutlu kahve
                    '#d84689', 
                    '#e87ea1', 
                    '#eab747', 
                    
                    '#9e0059'
                    ];

function SBO_ShowElem(elemId){
    let elem = document.getElementById(elemId);                          
    const rect = elem.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    const isVisible = (rect.top >= 0 && rect.bottom <= viewportHeight) ||
                      (rect.top < 0 && rect.bottom > 0 && rect.height <= viewportHeight) || // Element starts above, but is smaller than viewport and visible
                      (rect.top >= 0 && rect.top < viewportHeight && rect.bottom > viewportHeight);
    if (!isVisible) {
        elem.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
function SBO_FormatTimeWindow(twNumeric, detailedLogs, indexInLogsArray){
    if(!twNumeric){
        return '';
    }
    let tw = '' + twNumeric;
    let twFirst8 = '';
    if(tw.length>8){
        twFirst8 = tw.substring(0,8);
    }
    let prevTw8 = ''; 
    if(indexInLogsArray>0){
        let prevTw = '' + detailedLogs[indexInLogsArray-1].tw;
        if (prevTw.length>8){
            prevTw8 = prevTw.substring(0,8);
        }
    }
    //like 202505230729
    let y = tw.substring(0,4);
    let m = tw.length>4 ? tw.substring(4,6) : '';
    let d = tw.length>6 ? tw.substring(6,8) : '';
    let h = tw.length>8 ? tw.substring(8,10) : '';
    let i = tw.length>10 ? tw.substring(10,12) : '';

    let rv = y;
    if(!m){
        return y;
    }
    rv = rv + '-' + m;
    if (!d){
        return rv;
    }
    rv = rv + '-' + d;                    
    if(!h){
        return rv;
    }

    if(prevTw8 === twFirst8 && twFirst8.length>0){
        rv = h;
    }
    else{
        rv = rv + ' ' + h;
    }

    if(!i){
        return rv;
    }
    return rv + ':' + i;                    
}

const SBOBarChart  = {
    props:{
        targetElementId : String,
        title: String,
        yAxisName: String,
        seriesName: String,
        barColor: String,
        hoverColor: String
    },
    data: function() {
        return {
            chartObj: null
        };
    },
    methods: {
        initChart(){
            let elem = document.getElementById(this.targetElementId);
            var self = this;
            this.$nextTick(()=>{
                self.chartObj = echarts.init(elem);
            });
            
        },
        showError(errMsg){
            let elem = document.getElementById(this.targetElementId);
            elem.innerText='Error loading chart:' + errMsg;
        },
        showChart(chartData){
            const option = {
                title: {
                    text: this.title,
                    left: 'center',
                    textStyle: {
                        fontSize: 16,
                        fontWeight: 'bold'
                    }
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow' // 'shadow' for bar charts, 'line' for line charts
                    },
                    formatter: '{b}<br/>{a}: ${c}' // Custom tooltip format
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true // Ensure labels are contained within the grid
                },
                xAxis: {
                    type: 'category',
                    data: chartData.xLabels,
                    axisLabel: {
                        rotate: 45, // Rotate labels for better readability if many categories
                        interval: 0 // Display all labels
                    },
                    axisTick: {
                        alignWithLabel: true
                    }
                },
                yAxis: {
                    type: 'value',
                    name: this.yAxisName,
                    axisLabel: {
                        formatter: '{value}'
                    }
                },
                series: [
                    {
                        name: this.seriesName,
                        type: 'bar',
                        data: chartData.values,
                        barMaxWidth: 30, 
                        itemStyle: {
                            borderRadius: [5, 5, 0, 0], // Rounded corners on top of bars
                            color: this.barColor || '#7209b7',                            
                        },
                        emphasis: {
                            maxWidth: 50,
                            itemStyle: {
                                color: this.hoverColor || '#f72585', // Highlight color on hover
                                
                            }
                        },
                        barWidth: '60%' // Adjust bar width
                    }
                ]
            };
            this.chartObj.setOption(option);
        }
    },
    mounted: function () {        
        this.initChart();
    },
    template: `
    <div> 
        <div class="card h-100 position-relative">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-bar-chart" :id="targetElementId"></div>
            </div>
        </div>    
    </div>`
};


const SBOPieChart  = {
    props:{
        targetElementId : String,
        title: String,
        seriesName: String,
        hideLegends: Boolean
    },
    data: function() {
        return {
            chartObj: null
        };
    },
    methods: {
        initChart(){
            let elem = document.getElementById(this.targetElementId);
            var self = this;
            this.$nextTick(()=>{
                self.chartObj = echarts.init(elem);
                });                
            
        },
        showError(errMsg){
            let elem = document.getElementById(this.targetElementId);
            elem.innerText='Error loading chart:' + errMsg;
        },
        showChart(chartData){
            const option = {
                title: {
                    text: this.title,
                    left: 'center',
                    textStyle: {
                        fontSize: 16,
                        fontWeight: 'bold'
                    }
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{b}: {c} ({d}%)' // Shows series name, item name, value, and percentage
                },
                legend: {
                    show: !this.hideLegends,
                    orient: 'vertical', // Vertical legend
                    left: 'left',       // Position legend to the left
                    data: chartData.legends
                },
                series: [
                    {
                        name: this.seriesName, // Series name for tooltip
                        type: 'pie',
                        radius: ['40%', '70%'], // Inner and outer radius for a donut chart effect
                        center: ['50%', '60%'], // Position of the chart center
                        data: chartData.values,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)' // Shadow effect on hover
                            }
                        },
                        label: {
                            show: true,
                            formatter: '{b}: {d}%', // Show category name and percentage on slices
                            overflow: 'truncate', // Truncate long labels
                            edgeDistance: '10%', // Distance from edge
                            lineHeight: 15,
                            fontSize: 12
                        },
                        labelLine: {
                            show: true,
                            length: 10,
                            length2: 10
                        }
                    }
                ],
                color: sboChartBgColors
            };
            this.chartObj.setOption(option);
        }
    },
    mounted: function () {        
        this.initChart();
    },
    template: `
    <div> 
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-pie-chart" :id="targetElementId"></div>
            </div>
        </div>    
    </div>`
};


const SBODetailedMetricsView  = {
    props:{
        showKeyValue: Boolean,
        elemId: String
    },
    data: function() {
        return {
            detailedLogs:[],
            page: 1,
            hasMoreResults: false,
            domainId:0,
            metricType: 0,
            keyValue: null,
            twStart:0,
            twEnd:0,
            limit:20,
            title:''
        };
    },
    methods: {
        initParams(domainIdParam, metricTypeParam, keyValueParam, twStartParam, twEndParam, limitParam, titleParam){
            this.domainId = domainIdParam;
            this.metricType = metricTypeParam;
            this.keyValue = keyValueParam;
            this.twStart = twStartParam;
            this.twEnd = twEndParam;
            this.limit = limitParam;
            this.title = titleParam;
        },
        formatTW(tw, index){
            return SBO_FormatTimeWindow(tw, this.detailedLogs, index);
        },
        goToPage(pageNo){
            var self = this;
            this.page = pageNo;
            var url = '../api/metrics?domainId='+this.domainId+'&metricType='+this.metricType+
                    '&twStart=' + this.twStart + '&twEnd=' + this.twEnd;
            if(this.keyValue){
                url+='&keyValue=' + encodeURIComponent(this.keyValue);
            }
            url+='&page=' + pageNo + '&limit=' + this.limit;
            window.fetch(url).then((response)=>{                        
                response.json().then((parsedJson)=>{                        
                    self.detailedLogs = parsedJson.data;
                    self.hasMoreResults = parsedJson.hasMoreResults;                    
                    SBO_ShowElem(this.elemId);
                    let lineChartData = {
                                xLabels:[],
                                values:[]
                            };
                    for(let rowIndex in parsedJson.data){
                        if(!parsedJson.data[rowIndex]){
                            continue;
                        }
                        lineChartData.values.push(parsedJson.data[rowIndex].metric);
                        lineChartData.xLabels.push(SBO_FormatTimeWindow(parsedJson.data[rowIndex].tw, parsedJson.data, rowIndex));
                    }
                    console.log(lineChartData);
                    self.$nextTick(()=>{
                        window.setTimeout(()=>{
                            self.$refs.lineChart.showChart(lineChartData);
                        }, 500);
                        
                    });
                });                        
            });
        },
    },
    mounted: function () {
    },
    template: `
<div class="table-responsive mt-2" v-bind:id="elemId">
    <div v-if="detailedLogs && detailedLogs.length>0" class="card">
        <div class="card-body">
            <h6 class="text-nowrap">{{ title }}</h6>
            <div class="row">
                <div class="col-4">
                    <table class="table table-sm small table-hover w-auto">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th v-if="showKeyValue">Key</th>
                                <th>Metric</th>
                            </tr>                                
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in detailedLogs">
                                <td v-if="row" class="text-end">{{ formatTW(row.tw, index) }}</td>
                                <td v-if="row && showKeyValue">{{ row.keyValue }}</td>
                                <td v-if="row" class="text-end">{{ row.metric }}</td>
                            </tr>
                        </tbody>                    
                    </table>
                    <div>
                        Page: {{ page }}
                        <button title="Previous page" class="btn btn-sm btn-link" v-if="page>1" v-on:click="goToPage(page-1)">
                            <i class="bi bi-skip-backward"></i>
                        </button>
                        <button title="Next page" class="btn btn-sm btn-link" v-if="hasMoreResults" v-on:click="goToPage(page+1)">
                            <i class="bi bi-skip-forward"></i>
                        </button>
                    </div>
                </div>
                <div class="col-8">
                    <sbo-linechart ref="lineChart" v-bind:no-border="true" v-bind:target-element-id="elemId +'_linechart'" v-bind:title="title" y-axis-name="Count" series-name="Counts"></sbo-linechart>
                </div>
            </div>
        </div>
    </div>
</div>`
};

const SBOLineChart  = {
    props:{
        targetElementId : String,
        title: String,
        yAxisName: String,
        seriesName: String,
        lineColor: String,
        hoverColor: String,
        noBorder: Boolean
    },
    data: function() {
        return {
            chartObj: null
        };
    },
    methods: {
        initChart(){
            let elem = document.getElementById(this.targetElementId);
            var self = this;
            this.$nextTick(()=>{
                self.chartObj = echarts.init(elem);
            });
            
        },
        showError(errMsg){
            let elem = document.getElementById(this.targetElementId);
            elem.innerText='Error loading chart:' + errMsg;
        },
        showChart(chartData){
            const option = {
                title: {
                    text: this.title,
                    left: 'center',
                    textStyle: {
                        fontSize: 16,
                        fontWeight: 'bold'
                    }
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    },
                    formatter: '{b}<br/>{a}: ${c}'
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: chartData.xLabels,
                    axisLabel: {
                        rotate: 45,
                        interval: 0
                    },
                    axisTick: {
                        alignWithLabel: true
                    }
                },
                yAxis: {
                    type: 'value',
                    name: this.yAxisName,
                    axisLabel: {
                        formatter: '{value}'
                    }
                },
                series: [
                    {
                        name: this.seriesName,
                        type: 'line',
                        data: chartData.values,
                        smooth: true,
                        itemStyle: {
                            color: this.lineColor || '#7209b7',                            
                        },
                        emphasis: {
                            focus: 'series'
                        }
                    }
                ]
            };
            this.chartObj.setOption(option);
        }
    },
    mounted: function () {        
        this.initChart();
    },
    template: `
    <div> 
        <div class="{'card h-100 position-relative':!noBorder, 'card h-100 position-relative border-0':noBorder}">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-bar-chart" :id="targetElementId"></div>
            </div>
        </div>    
    </div>`
};