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


const SBOBarChart  = {
    props:{
        targetElementId : String,
        title: String,
        yAxisName: String,
        seriesName: String
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
                            color: '#7209b7',                            
                        },
                        emphasis: {
                            maxWidth: 50,
                            itemStyle: {
                                color: '#f72585', // Highlight color on hover
                                
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
        seriesName: String
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
                    formatter: '{a} <br/>{b}: {c} ({d}%)' // Shows series name, item name, value, and percentage
                },
                legend: {
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
        <div class="card h-100 position-relative">
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="sbo-pie-chart" :id="targetElementId"></div>
            </div>
        </div>    
    </div>`
};