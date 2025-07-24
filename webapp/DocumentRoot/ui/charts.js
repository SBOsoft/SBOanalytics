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
        elem.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function SBO_TimeWindowToDate(twNumeric){
    if(!twNumeric){
        return '';
    }
    let tw = '' + twNumeric;
    let twFirst8 = '';
    if(tw.length>8){
        twFirst8 = tw.substring(0,8);
    }
    //like 202505230729
    let y = tw.substring(0,4);
    let m = tw.length>4 ? (tw.substring(4,6) - 1): 0;
    let d = tw.length>6 ? tw.substring(6,8) : 0;
    let h = tw.length>8 ? tw.substring(8,10) : 0;
    let i = tw.length>10 ? tw.substring(10,12) : 0;
    
    let dt = new Date(y, m, d, h, i, 0);
    
    return dt;
}

function SBO_FormatDateAsTS(dateObject){
    const yyyy = dateObject.getFullYear();
    // Months are 0-indexed, so add 1
    const mm = String(dateObject.getMonth() + 1).padStart(2, '0');
    const dd = String(dateObject.getDate()).padStart(2, '0');
    const HH = String(dateObject.getHours()).padStart(2, '0');
    const MM = String(dateObject.getMinutes()).padStart(2, '0');
    const ss = String(dateObject.getSeconds()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd} ${HH}:${MM}:${ss}`;
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
        hoverColor: String,
        vertical: Boolean
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
            if(this.vertical){
                chartData.values.reverse();
                chartData.legends.reverse();
            }
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
                    type: this.vertical ? 'value':'category',
                    data: this.vertical ? chartData.values: chartData.legends,
                    axisLabel: {
                        rotate: this.vertical ? 0 : 45,
                        interval: 0
                    },
                    axisTick: {
                        alignWithLabel: true
                    }
                },
                yAxis: {
                    type: this.vertical ? 'category':'value',
                    name: this.yAxisName,
                    axisLabel: {
                        formatter: '{value}',
                        interval: 0
                    },
                    axisTick: {
                        alignWithLabel: true
                    },
                    data: this.vertical ? chartData.legends : chartData.values
                },
                series: [
                    {
                        name: this.seriesName,
                        type: 'bar',
                        data: chartData.values,
                        barMaxWidth: 30, 
                        itemStyle: this.vertical ? {
                            borderRadius: [0, 5, 5, 0],
                            color: this.barColor || '#7209b7',                            
                        } : {
                            borderRadius: [5, 5, 0, 0],
                            color: this.barColor || '#7209b7',                            
                        },
                        emphasis: {
                            maxWidth: 50,
                            itemStyle: {
                                color: this.hoverColor || '#f72585',
                                
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
        showKeyValueProp: Boolean,
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
            showKeyValue: this.showKeyValueProp,
            twStart:0,
            twEnd:0,
            timeWindowSizeMinutes: 10,
            limit:20,
            title:'',
            groupBy:'',
            enlarged:false,
            stylesBeforeEnlarge: {}
        };
    },
    methods: {
        initParams(domainIdParam, metricTypeParam, keyValueParam, twStartParam, twEndParam, limitParam, titleParam, allDomains){
            this.domainId = domainIdParam;
            this.metricType = metricTypeParam;
            this.keyValue = keyValueParam;
            this.twStart = twStartParam;
            this.twEnd = twEndParam;
            this.timeWindowSizeMinutes = allDomains[domainIdParam].timeWindowSizeMinutes || 10;
            this.limit = limitParam;
            this.title = titleParam;
        },
        hideDetails(){
            document.getElementById(this.elemId).style.display='none';
        },
        resize(){
            if(this.enlarged){
                this.enlarged = false;
                let elem = document.getElementById(this.elemId);
                elem.style.background=this.stylesBeforeEnlarge.background;
                elem.style.position=this.stylesBeforeEnlarge.position;
                elem.style.left=this.stylesBeforeEnlarge.left;
                elem.style.top=this.stylesBeforeEnlarge.top;
                elem.style.width=this.stylesBeforeEnlarge.width;
                elem.style.height=this.stylesBeforeEnlarge.height;
                elem.style.zIndex=this.stylesBeforeEnlarge.zIndex;
                elem.style.padding = this.stylesBeforeEnlarge.padding;
                elem.style.margin = this.stylesBeforeEnlarge.margin;
                this.$refs.lineChart.resize();
            }
            else{
                let elem = document.getElementById(this.elemId);
                this.stylesBeforeEnlarge.background = elem.style.background;
                this.stylesBeforeEnlarge.position = elem.style.position;
                this.stylesBeforeEnlarge.left = elem.style.left;
                this.stylesBeforeEnlarge.top = elem.style.top;
                this.stylesBeforeEnlarge.width = elem.style.width;
                this.stylesBeforeEnlarge.height = elem.style.height;
                this.stylesBeforeEnlarge.zIndex = elem.style.zIndex;
                this.stylesBeforeEnlarge.padding =  elem.style.padding;
                this.stylesBeforeEnlarge.margin = elem.style.margin;

                elem.style.background = '#fff';
                elem.style.position='fixed';
                elem.style.left=0;
                elem.style.top=0;
                elem.style.padding='20px 20px 20px 20px';
                elem.style.width=window.innerWidth+'px';
                elem.style.height=document.documentElement.clientHeight+'px';
                elem.style.zIndex=99;
                elem.style.margin='0 0 0 0';
                this.enlarged = true;
                this.$refs.lineChart.resize();
            }
        },
        formatTW(tw, index){
            return SBO_FormatTimeWindow(tw, this.detailedLogs, index);
        },
        removeKeyValueFilter(){
            this.keyValue = null;
            this.title = 'All results';
            this.showKeyValue = true;
            this.goToPage(1);
        },
        goToPage(pageNo, groupByParam){
            if(groupByParam){
                this.groupBy = groupByParam;
            }
            document.getElementById(this.elemId).style.display='';
            var self = this;
            this.page = pageNo;
            var url = '../api/metrics?domainId='+this.domainId+'&metricType='+this.metricType+
                    '&twStart=' + this.twStart + '&twEnd=' + this.twEnd;
            if(this.keyValue){
                url+='&keyValue=' + encodeURIComponent(this.keyValue);
            }
            if(this.groupBy){
                url+='&groupBy=' + encodeURIComponent(this.groupBy);
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
                    self.$nextTick(()=>{
                        window.setTimeout(()=>{
                            self.$refs.lineChart.showChart(lineChartData);
                        }, 500);
                        
                    });
                });                        
            });
        },
        getLogsLink(row){
            var link ='logs?domainId=' + encodeURIComponent(this.domainId);
            //calculate tw start end row.tw is numeric timewindo value and this.timeWindowSizeMinutes is the size
            //if this.groupBy is empty then use timeWindowSizeMinutes, otherwise use this.groupBy to calculate start - end times
            let startDt = SBO_TimeWindowToDate(row.tw);
            let startTwTS = SBO_FormatDateAsTS(startDt);
            link+='&twStartTS=' + encodeURIComponent(startTwTS);
            switch(this.groupBy){
                case 'hour':
                    startDt.setHours(startDt.getHours() + 1);
                    break;
                case 'day':
                    startDt.setDate(startDt.getDate() + 1);
                    break;
                case 'month':
                    startDt.setMonth(startDt.getMonth() + 1);
                    break;
                default:
                    startDt.setMinutes(startDt.getMinutes() + this.timeWindowSizeMinutes);
                    break;                    
            }
            link+='&twEndTS=' + encodeURIComponent(SBO_FormatDateAsTS(startDt));
            switch(this.metricType){
                case 3:
                    link+='&keyName=statusCode';
                    break;
                case 4:
                    link+='&keyName=clientIP';
                    break;
                case 5:
                    link+='&keyName=method';
                    break;
                case 6:
                    link+='&keyName=referer';
                    break;
                case 7:
                    link+='&keyName=basePath';
                    break;
                case 11:
                    link+='&keyName=uaFamily';
                    break;
                case 12:
                    link+='&keyName=uaOS';
                    break;
                case 13:
                    link+='&keyName=deviceType';
                    break;
                case 14:
                    link+='&keyName=isHuman';
                    break;
                case 15:
                    link+='&keyName=requestIntent';
                    break;
            }
            link+='&keyValue=' + encodeURIComponent(row.keyValue);
            console.log(link);
            return link;
        }
    },
    mounted: function () {
    },
    template: `
<div class="table-responsive mt-2" v-bind:id="elemId">
    <div v-if="detailedLogs && detailedLogs.length>0" class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between">
                <h6 class="text-nowrap">{{ title }}</h6>
                <div>
<button v-on:click="hideDetails" class="btn btn-outline"><i class="bi bi-x-lg"></i></button>
<button v-on:click="resize" class="btn btn-outline"><i class="bi bi-arrows-fullscreen"></i></button></div>
            </div>
            <div class="row">
                <div class="col-md-4">
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
                                <td v-if="row" class="text-end"><a v-bind:href="getLogsLink(row)" target="_blank" title="View logs">{{ row.metric }}</a></td>
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
                <div class="col-md-8">
                    <sbo-linechart ref="lineChart" v-bind:no-border="true" v-bind:target-element-id="elemId +'_linechart'" v-bind:title="title" y-axis-name="Count" series-name="Counts"></sbo-linechart>
                </div>
            </div>
            <div v-if="keyValue">Showing results for {{ keyValue }}. <button v-on:click="removeKeyValueFilter()" class="btn btn-sm btn-link">Show all</button> </div>
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
        resize(){
            if(this.chartObj){
                /*
                let self = this;
                self.chartObj.resize();
                //this.showChart();                
                this.$nextTick(()=>{
                  self.showChart();  
                });
                */
            }
        },
        showError(errMsg){
            let elem = document.getElementById(this.targetElementId);
            elem.innerText='Error loading chart:' + errMsg;
        },
        showChart(chartData){
            const option = {
                animation: false,
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
                        smooth: false,
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



const SBOLogsView  = {
    props:{        
        elemId: String
    },
    data: function() {
        return {
            logData:[],
            page: 1,
            hasMoreResults: false,
            domainId:0,
            keyName: null,
            keyValue: null,
            twStart:null,
            twEnd:null,
            limit:20,
            title:''
        };
    },
    methods: {
        initParams(domainIdParam, keyNameParam, keyValueParam, twStartParam, twEndParam, limitParam, titleParam){
            this.domainId = domainIdParam;
            this.keyName = keyNameParam;
            this.keyValue = keyValueParam;
            this.twStart = twStartParam;
            this.twEnd = twEndParam;
            this.limit = limitParam;
            this.title = titleParam;
        },        
        removeKeyValueFilter(){
            this.keyName = null;
            this.keyValue = null;
            this.title = 'All results';
            this.showKeyValue = true;
            this.goToPage(1);
        },
        goToPage(pageNo, groupByParam){
            if(groupByParam){
                this.groupBy = groupByParam;
            }

            var self = this;
            this.page = pageNo;

            var url = '../api/logs?domainId='+this.domainId+
                    '&twStart=' + encodeURIComponent(this.twStart) + '&twEnd=' + encodeURIComponent(this.twEnd);
            if(this.keyName){
                url+='&keyName=' + encodeURIComponent(this.keyName);
            }
            if(this.keyValue){
                url+='&keyValue=' + encodeURIComponent(this.keyValue);
            }
            
            url+='&page=' + pageNo + '&limit=' + this.limit;
            window.fetch(url).then((response)=>{                        
                response.json().then((parsedJson)=>{                        
                    self.logData = parsedJson.data;
                    self.hasMoreResults = parsedJson.hasMoreResults;                    
                    SBO_ShowElem(this.elemId);                    
                });                        
            });
        },
    },
    mounted: function () {
    },
    template: `
<div class="mt-2" v-bind:id="elemId">
    <div v-if="logData && logData.length>0" class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between">
                <h6 class="text-nowrap">{{ title }}</h6>
            </div>
            <div class="table-responsive">
            <table class="table table-sm small table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Client IP</th>
                        <th>User</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Bytes Sent</th>
                        <th>Request URI</th>
                        <th>User agent</th>
                        <th>Referer</th>
                    </tr>                                
                </thead>
                <tbody>
                    <tr v-for="(row, index) in logData">
                        <td v-if="row">{{ row.requestTimestamp }}</td>
                        <td v-if="row">{{ row.clientIP }}</td>
                        <td v-if="row">{{ row.remoteUser }}</td>
                        <td v-if="row">{{ row.method }}</td>
                        <td v-if="row">{{ row.statusCode }}</td>
                        <td v-if="row">{{ row.bytesSent }}</td>
                        <td v-if="row">{{ row.requestUri }}</td>
                        <td v-if="row">{{ row.uaString }}</td>
                        <td v-if="row">{{ row.referer }}</td>
                    </tr>
                </tbody>                    
            </table>
            </div>
            <div>
                Page: {{ page }}
                <button title="Previous page" class="btn btn-sm btn-link" v-if="page>1" v-on:click="goToPage(page-1)">
                    <i class="bi bi-skip-backward"></i>
                </button>
                <button title="Next page" class="btn btn-sm btn-link" v-if="hasMoreResults" v-on:click="goToPage(page+1)">
                    <i class="bi bi-skip-forward"></i>
                </button>
            </div>
            <!--div v-if="keyValue">Showing results for {{ keyName }} = {{ keyValue }}. <button v-on:click="removeKeyValueFilter()" class="btn btn-sm btn-link">Show all</button> </div-->
        </div>
    </div>
</div>`
};