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

/*
 Fetches url and parses the response as JSON. Rejects on non-2xx responses and on
 responses that are not valid JSON (e.g. an HTML login page after the session expired),
 so callers can handle every failure in a single .catch().
 For non-2xx responses the error message comes from the api's error JSON
 ({"error":"...","description":"..."}, see SBO_API_Error_Response) when present.
 */
function SBO_FetchJson(url){
    return window.fetch(url).then((response)=>{
        if(!response.ok){
            return response.text().then((body)=>{
                const httpStatus = 'HTTP ' + response.status + (response.statusText ? ' ' + response.statusText : '');
                let msg = httpStatus;
                try{
                    const parsed = JSON.parse(body);
                    if(parsed && parsed.error){
                        msg = parsed.error + (parsed.description ? ': ' + parsed.description : '') + ' (' + httpStatus + ')';
                    }
                }
                catch(e){
                    //body is not JSON, keep the HTTP status message
                }
                throw new Error(msg);
            });
        }
        return response.json().catch(()=>{
            throw new Error('Unexpected response from server, your session may have expired. Reload the page.');
        });
    });
}

function SBO_ShowElem(elemId){
    let elem = document.getElementById(elemId);
    if(!elem){
        return;
    }
    const rect = elem.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    const isVisible = (rect.top >= 0 && rect.bottom <= viewportHeight) ||
                      (rect.top < 0 && rect.bottom > 0 && rect.height <= viewportHeight) || // Element starts above, but is smaller than viewport and visible
                      (rect.top >= 0 && rect.top < viewportHeight && rect.bottom > viewportHeight);
    if (!isVisible) {
        elem.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/*
 Time window values (like 202505230729) are server wall-clock times. They are converted to
 Date objects in UTC and formatted back with the UTC getters, so the browser's time zone and
 daylight saving rules never shift them. Returns null for an empty value.
 */
function SBO_TimeWindowToDate(twNumeric){
    if(!twNumeric){
        return null;
    }
    let tw = '' + twNumeric;
    //like 202505230729
    let y = Number(tw.substring(0,4));
    let m = tw.length>4 ? Number(tw.substring(4,6)) - 1 : 0;
    let d = tw.length>6 ? Number(tw.substring(6,8)) : 1;
    let h = tw.length>8 ? Number(tw.substring(8,10)) : 0;
    let i = tw.length>10 ? Number(tw.substring(10,12)) : 0;

    return new Date(Date.UTC(y, m, d, h, i, 0));
}

/*
 Formats a Date created by SBO_TimeWindowToDate as 'YYYY-MM-DD HH:MM:SS' (24 hour).
 */
function SBO_FormatDateAsTS(dateObject){
    const yyyy = dateObject.getUTCFullYear();
    // Months are 0-indexed, so add 1
    const mm = String(dateObject.getUTCMonth() + 1).padStart(2, '0');
    const dd = String(dateObject.getUTCDate()).padStart(2, '0');
    const HH = String(dateObject.getUTCHours()).padStart(2, '0');
    const MM = String(dateObject.getUTCMinutes()).padStart(2, '0');
    const ss = String(dateObject.getUTCSeconds()).padStart(2, '0');
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
    //index may arrive as a string when callers iterate with for...in
    let index = Number(indexInLogsArray);
    let prevTw8 = '';
    if(index>0 && detailedLogs[index-1]){
        let prevTw = '' + detailedLogs[index-1].tw;
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

function SBO_GetLogsLink(domainId, tw, groupBy, timeWindowSizeMinutes, metricType, keyValue){
    var link ='logs?domainId=' + encodeURIComponent(domainId);
    //tw is the numeric time window value. If groupBy is empty the window is timeWindowSizeMinutes long,
    //otherwise groupBy (hour, day, month) determines the window length
    let startDt = SBO_TimeWindowToDate(tw);
    if(startDt){
        link+='&twStartTS=' + encodeURIComponent(SBO_FormatDateAsTS(startDt));
        let endDt = new Date(startDt.getTime());
        switch(groupBy){
            case 'hour':
                endDt.setUTCHours(endDt.getUTCHours() + 1);
                break;
            case 'day':
                endDt.setUTCDate(endDt.getUTCDate() + 1);
                break;
            case 'month':
                endDt.setUTCMonth(endDt.getUTCMonth() + 1);
                break;
            default:
                endDt.setUTCMinutes(endDt.getUTCMinutes() + (Number(timeWindowSizeMinutes) || 10));
                break;
        }
        //logs api filters with BETWEEN (inclusive), end one second before the next window starts
        endDt.setUTCSeconds(endDt.getUTCSeconds() - 1);
        link+='&twEndTS=' + encodeURIComponent(SBO_FormatDateAsTS(endDt));
    }
    switch(Number(metricType)){
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
    link+='&keyValue=' + encodeURIComponent(keyValue ?? '');

    return link;
}

/*
 Shared behaviour for the ECharts based components (SBOBarChart, SBOPieChart, SBOLineChart).
 Components using this mixin must declare a targetElementId prop, put ref="chartElem" on the
 chart container and implement buildOption(chartData) returning an ECharts option object.
 - The chart follows its container's size (window resize, full screen toggle) via ResizeObserver.
 - showChart() calls made before the chart is initialized are kept and rendered after init.
 - The ECharts instance is disposed when the component is unmounted.
 */
const SBOChartMixin = {
    data: function() {
        return {
            chartObj: null
        };
    },
    created: function () {
        //not reactive on purpose: Vue proxies must not wrap these
        this.resizeObserver = null;
        this.pendingChartData = null;
    },
    methods: {
        initChart(){
            const elem = this.$refs.chartElem;
            //markRaw: a reactive proxy around the ECharts instance breaks some ECharts internals
            this.chartObj = Vue.markRaw(echarts.init(elem));
            this.resizeObserver = new ResizeObserver(()=>{
                if(this.chartObj){
                    this.chartObj.resize();
                }
            });
            this.resizeObserver.observe(elem);
            if(this.pendingChartData){
                const pending = this.pendingChartData;
                this.pendingChartData = null;
                this.showChart(pending);
            }
        },
        disposeChart(){
            if(this.resizeObserver){
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }
            if(this.chartObj){
                this.chartObj.dispose();
                this.chartObj = null;
            }
        },
        resize(){
            if(this.chartObj){
                this.chartObj.resize();
            }
        },
        showChart(chartData){
            if(!this.chartObj){
                this.pendingChartData = chartData;
                return;
            }
            //notMerge=true: replace the previous option completely so series from an earlier call do not linger
            this.chartObj.setOption(this.buildOption(chartData), true);
        },
        showError(errMsg){
            if(!this.chartObj){
                return;
            }
            this.chartObj.setOption({
                title: {
                    text: 'Error loading chart: ' + errMsg,
                    left: 'center',
                    top: 'middle',
                    textStyle: {
                        fontSize: 14,
                        color: '#dc3545'
                    }
                }
            }, true);
        }
    },
    mounted: function () {
        this.initChart();
    },
    beforeUnmount: function () {
        this.disposeChart();
    }
};

const SBOBarChart  = {
    mixins: [SBOChartMixin],
    props:{
        targetElementId : String,
        title: String,
        yAxisName: String,
        seriesName: String,
        barColor: String,
        hoverColor: String,
        vertical: Boolean
    },
    methods: {
        buildOption(chartDataParam){
            let catValues = [];
            let cd = [];
            if(!Array.isArray(chartDataParam)){
                catValues = chartDataParam.legends || [];
                cd = (chartDataParam.values || []).slice();
            }
            else{
                for(const item of chartDataParam){
                    if(!item){
                        continue;
                    }
                    catValues.push(item.name);
                    cd.push(item);
                }
            }

            const categoryAxis = {
                type: 'category',
                data: catValues,
                axisLabel: {
                    rotate: this.vertical ? 0 : 45,
                    interval: 0
                },
                axisTick: {
                    alignWithLabel: true
                }
            };
            const valueAxis = {
                type: 'value',
                axisLabel: {
                    formatter: '{value}'
                }
            };

            return {
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
                    formatter: this.vertical ? '{b}: {c}' : '{b}<br/>{a}: {c}'
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true // Ensure labels are contained within the grid
                },
                xAxis: this.vertical ? valueAxis : categoryAxis,
                yAxis: Object.assign({name: this.yAxisName}, this.vertical ? categoryAxis : valueAxis),
                series: [
                    {
                        name: this.seriesName,
                        type: 'bar',
                        data: cd,
                        barMaxWidth: 30,
                        itemStyle: this.vertical ? {
                            borderRadius: [0, 5, 5, 0],
                            color: this.barColor || '#7209b7',
                        } : {
                            borderRadius: [5, 5, 0, 0],
                            color: this.barColor || '#7209b7',
                        },
                        emphasis: {
                            itemStyle: {
                                color: this.hoverColor || '#f72585'
                            }
                        },
                        barWidth: '60%' // Adjust bar width
                    }
                ]
            };
        }
    },
    template: `
    <div>
        <div class="card h-100 position-relative">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-bar-chart" ref="chartElem" :id="targetElementId"></div>
            </div>
        </div>
    </div>`
};


const SBOPieChart  = {
    mixins: [SBOChartMixin],
    props:{
        targetElementId : String,
        title: String,
        seriesName: String,
        hideLegends: Boolean
    },
    methods: {
        buildOption(chartData){
            return {
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
                    formatter: '{b}: {c} ({d}%)' // Shows item name, value, and percentage
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
        }
    },
    template: `
    <div>
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-pie-chart" ref="chartElem" :id="targetElementId"></div>
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
            stylesBeforeEnlarge: {},
            errorMessage:'',
            requestSeq:0
        };
    },
    methods: {
        initParams(domainIdParam, metricTypeParam, keyValueParam, twStartParam, twEndParam, limitParam, titleParam, allDomains){
            this.domainId = domainIdParam;
            this.metricType = metricTypeParam;
            this.keyValue = keyValueParam;
            this.twStart = twStartParam;
            this.twEnd = twEndParam;
            this.timeWindowSizeMinutes = allDomains?.[domainIdParam]?.timeWindowSizeMinutes || 10;
            this.limit = limitParam;
            this.title = titleParam;
        },
        hideDetails(){
            if(this.enlarged){
                //restore normal layout first, otherwise the panel reappears in full screen next time
                this.toggleFullscreen();
            }
            document.getElementById(this.elemId).style.display='none';
        },
        toggleFullscreen(){
            //the line chart follows its container size by itself (ResizeObserver in SBOChartMixin)
            const elem = document.getElementById(this.elemId);
            const styleKeys = ['background', 'position', 'left', 'top', 'width', 'height', 'zIndex', 'padding', 'margin', 'overflow'];
            if(this.enlarged){
                for(const key of styleKeys){
                    elem.style[key] = this.stylesBeforeEnlarge[key];
                }
                this.enlarged = false;
            }
            else{
                for(const key of styleKeys){
                    this.stylesBeforeEnlarge[key] = elem.style[key];
                }
                Object.assign(elem.style, {
                    background: '#fff',
                    position: 'fixed',
                    left: '0',
                    top: '0',
                    width: '100vw',
                    height: '100vh',
                    zIndex: '99',
                    padding: '20px',
                    margin: '0',
                    overflow: 'auto'
                });
                this.enlarged = true;
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
            //groupByParam undefined: keep the current grouping (paging). Empty string: no grouping.
            if(groupByParam !== undefined){
                this.groupBy = groupByParam || '';
            }
            document.getElementById(this.elemId).style.display='';
            const self = this;
            const requestId = ++this.requestSeq;
            this.page = pageNo;
            this.errorMessage = '';
            var url = '../api/metrics?domainId=' + encodeURIComponent(this.domainId) +
                    '&metricType=' + encodeURIComponent(this.metricType) +
                    '&twStart=' + encodeURIComponent(this.twStart) + '&twEnd=' + encodeURIComponent(this.twEnd);
            if(this.keyValue){
                url+='&keyValue=' + encodeURIComponent(this.keyValue);
            }
            if(this.groupBy){
                url+='&groupBy=' + encodeURIComponent(this.groupBy);
            }
            url+='&page=' + encodeURIComponent(pageNo) + '&limit=' + encodeURIComponent(this.limit);
            SBO_FetchJson(url).then((parsedJson)=>{
                if(requestId !== self.requestSeq){
                    return; //a newer request was started, drop this stale response
                }
                const rows = Array.isArray(parsedJson.data) ? parsedJson.data : [];
                self.detailedLogs = rows;
                self.hasMoreResults = parsedJson.hasMoreResults;
                let lineChartData = {
                            xLabels:[],
                            values:[]
                        };
                rows.forEach((row, rowIndex)=>{
                    if(!row){
                        return;
                    }
                    lineChartData.values.push(row.metric);
                    lineChartData.xLabels.push(SBO_FormatTimeWindow(row.tw, rows, rowIndex));
                });
                self.$nextTick(()=>{
                    SBO_ShowElem(self.elemId);
                    if(self.$refs.lineChart){
                        self.$refs.lineChart.showChart(lineChartData);
                    }
                });
            }).catch((err)=>{
                if(requestId !== self.requestSeq){
                    return;
                }
                self.detailedLogs = [];
                self.hasMoreResults = false;
                self.errorMessage = 'Error loading data: ' + err.message;
                self.$nextTick(()=>{
                    SBO_ShowElem(self.elemId);
                    if(self.$refs.lineChart){
                        self.$refs.lineChart.showError(err.message);
                    }
                });
            });
        },
        getLogsLink(row){
            return SBO_GetLogsLink(this.domainId, row.tw, this.groupBy, this.timeWindowSizeMinutes, this.metricType, row.keyValue);
        }
    },
    template: `
<div class="table-responsive mt-2" v-bind:id="elemId">
    <div v-if="(detailedLogs && detailedLogs.length>0) || errorMessage" class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between">
                <h6 class="text-nowrap">{{ title }}</h6>
                <div>
<button v-on:click="hideDetails" class="btn btn-outline" title="Close"><i class="bi bi-x-lg"></i></button>
<button v-on:click="toggleFullscreen" class="btn btn-outline" title="Toggle full screen"><i class="bi bi-arrows-fullscreen"></i></button></div>
            </div>
            <div v-if="errorMessage" class="alert alert-danger py-1 small">{{ errorMessage }}</div>
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
    mixins: [SBOChartMixin],
    props:{
        targetElementId : String,
        title: String,
        yAxisName: String,
        seriesName: String,
        lineColor: String,
        hoverColor: String,
        noBorder: Boolean,
        hideLegends: Boolean
    },
    methods: {
        /*
         chartData is either {xLabels:[...], values:[...]} for a single series, or
         {'series name': [{name, value}, ...], ...} for multiple series that share the same x labels.
         */
        buildOption(chartData){
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
                    //default axis tooltip lists every series at the hovered x label
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    show: !this.hideLegends,
                    orient: 'horizontal',
                    bottom: '10',
                    data: Array.isArray(chartData.legends) ? chartData.legends : null
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
                    //data is set below
                    axisLabel: {
                        rotate: 45
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
                }
            };
            if(Array.isArray(chartData.values)){
                option.series = [
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
                ];
                option.xAxis.data = chartData.xLabels;
            }
            else{
                let seriesObjects = [];
                let xLabels = [];
                let i = 0;
                for(let serieName in chartData){
                    seriesObjects.push({
                        name: serieName,
                        type: 'line',
                        data: chartData[serieName],
                        smooth: false,
                        showSymbol:false,
                        coordinateSystem: 'cartesian2d',
                        itemStyle: {
                            color: sboChartBgColors[i] || '#7209b7',
                        },
                        emphasis: {
                            focus: 'series'
                        }
                    });
                    if(i==0){
                        for(const point of chartData[serieName]){
                            xLabels.push(point.name);
                        }
                    }
                    i++;
                }
                option.series = seriesObjects;
                option.xAxis.data = xLabels;
            }
            return option;
        }
    },
    template: `
    <div>
        <div v-bind:class="{'card h-100 position-relative':!noBorder, 'card h-100 position-relative border-0':noBorder}">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-bar-chart" ref="chartElem" :id="targetElementId"></div>
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
            title:'',
            errorMessage:'',
            requestSeq:0
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
            this.goToPage(1);
        },
        calcRowNo(rowIndex){
            return (this.limit * (this.page-1)) + rowIndex + 1;
        },
        getUAIcon(row){
            const uaFamily = row.uaFamily || '';
            if(uaFamily==='Script' || uaFamily.endsWith('Bot') || uaFamily==='Scanner'){
                return '<i class="bi bi-robot text-warning me-1"></i>';
            }
            if(row.uaOS==='Windows'){
                return '<i class="bi bi-windows text-primary me-1"></i>';
            }
            if(row.uaOS==='MacOS'){
                return '<i class="bi bi-apple text-secondary me-1"></i>';
            }
            if(row.uaOS==='Android'){
                return '<i class="bi bi-android2 text-success me-1"></i>';
            }
            if(row.uaOS==='IOS'){
                return '<i class="bi bi-tablet text-info me-1"></i>';
            }
            return '';
        },
        goToPage(pageNo){
            const self = this;
            const requestId = ++this.requestSeq;
            this.page = pageNo;
            this.errorMessage = '';

            var url = '../api/logs?domainId=' + encodeURIComponent(this.domainId) +
                    '&twStart=' + encodeURIComponent(this.twStart) + '&twEnd=' + encodeURIComponent(this.twEnd);
            if(this.keyName){
                url+='&keyName=' + encodeURIComponent(this.keyName);
            }
            if(this.keyValue){
                url+='&keyValue=' + encodeURIComponent(this.keyValue);
            }

            url+='&page=' + encodeURIComponent(pageNo) + '&limit=' + encodeURIComponent(this.limit);
            SBO_FetchJson(url).then((parsedJson)=>{
                if(requestId !== self.requestSeq){
                    return; //a newer request was started, drop this stale response
                }
                self.logData = Array.isArray(parsedJson.data) ? parsedJson.data : [];
                self.hasMoreResults = parsedJson.hasMoreResults;
                self.$nextTick(()=>{
                    SBO_ShowElem(self.elemId);
                });
            }).catch((err)=>{
                if(requestId !== self.requestSeq){
                    return;
                }
                self.logData = [];
                self.hasMoreResults = false;
                self.errorMessage = 'Error loading logs: ' + err.message;
            });
        },
    },
    template: `
<div class="mt-2" v-bind:id="elemId">
    <div v-if="errorMessage" class="alert alert-danger py-1 small">{{ errorMessage }}</div>
    <div v-if="logData && logData.length>0" class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between">
                <h6 class="text-nowrap">{{ title }}</h6>
            </div>
            <div class="table-responsive">
            <table class="table table-sm small table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th></th>
                        <th>Time</th>
                        <th>Client IP</th>
                        <th>User</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Bytes Sent</th>
                        <th>Path</th>
                        <th>User agent</th>
                        <th>Referer</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in logData" v-bind:class="{'sbo-status400':(row && row.statusCode>=400 && row.statusCode<500), 'sbo-status500':(row && row.statusCode>=500)}">
                        <td v-if="row">{{ calcRowNo(index) }}</td>
                        <td v-if="row"><span v-html="getUAIcon(row)"></span></td>
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
