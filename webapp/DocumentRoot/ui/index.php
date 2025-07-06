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
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: 0.75rem;
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
        <div class="text-center bg-warning">
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
        </div>

        <div class="container-fluid py-5">

            <div v-if="loading" class="text-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading metrics data...</p>
            </div>

            <div v-if="error" class="alert alert-danger text-center mx-auto" style="max-width: 600px;" role="alert">
                {{ error }}
            </div>

            <div class="row justify-content-center">
                <div class="col-md-5 col-xl-4">
                    <sbo-barchart ref="totalRequestsBarChart" target-element-id="totalRequestsBarChart" title="Total requests" y-axis-name="Req. Count" series-name="Requests"></sbo-barchart>                    
                </div>
                <div class="col-md-5 col-xl-4">
                    <sbo-piechart ref="statusCodesPieChart" target-element-id="statusCodesPieChart" title="Response status codes"  series-name="Status codes"></sbo-piechart>                    
                </div>
                <!-- Bar Chart Section -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 position-relative">
                        <div class="card-header">Bar Chart: Monthly Sales Performance</div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="barChart" class="sbo-bar-chart"></div>
                        </div>
                    </div>
                </div>

                <!-- Pie Chart Section -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 position-relative">
                        <div class="card-header">Pie Chart: Revenue Distribution by Category</div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="pieChart" class="sbo-pie-chart"></div>
                        </div>
                    </div>
                </div>

                <!-- Stacked Line Chart Section -->
                <div class="col-12 mb-4">
                    <div class="card h-100 position-relative">
                        <div class="card-header">Stacked Line Chart: Product Performance Over Time</div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="stackedLineChart" class="sbo-sline-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const app = Vue.createApp({
            components:{
                SBOBarChart,
                SBOPieChart,
            },
            // Data properties for the component
            data() {
                return {
                    twStart:202505010000,
                    twEnd: 202506010000,
                    allDomains:[],
                    domainId: 1,    //TODO fix
                    chartBgColors:   
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
                    ],
                    barChart: null,          // ECharts instance for the bar chart
                    pieChart: null,          // ECharts instance for the pie chart
                    stackedLineChart: null,  // ECharts instance for the stacked line chart
                    loading: true,           // Loading state for data fetching
                    error: null,             // Error message if data fetching fails
                    resizeTimer: null        // Timer for debouncing window resize
                };
            },
            // Lifecycle hook: called after the component is mounted to the DOM
            mounted() {
                this.loadAllDomains();
                // Initialize ECharts instances for each chart container
                this.initCharts();
                // Fetch data from the simulated API and update charts
                this.fetchMetricsData();
                
                this.loadTotalRequestsData();
                this.loadRequestsByStatusCodesData();

                // Add a global event listener for window resize to make charts responsive
                // We debounce the resize event to prevent excessive re-rendering
                window.addEventListener('resize', this.resizeCharts);
            },
            // Lifecycle hook: called before the component is unmounted from the DOM
            beforeUnmount() {
                // Clean up the resize event listener to prevent memory leaks
                window.removeEventListener('resize', this.resizeCharts);
                // Dispose ECharts instances to free up resources
                if (this.barChart) this.barChart.dispose();
                if (this.pieChart) this.pieChart.dispose();
                if (this.stackedLineChart) this.stackedLineChart.dispose();
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
                loadTotalRequestsData(){
                    var self = this;
                    var url = '../api/metrics?domainId='+this.domainId+'&metricType=1&twStart=' + this.twStart + '&twEnd=' + this.twEnd;
                    window.fetch(url).then((response)=>{                        
                        response.json().then((parsedJson)=>{
                        console.log("parsedJson in loadTotalRequestsData");    
                        console.log(parsedJson);
                            let chartData = {
                                xLabels:[],
                                values:[]
                            };
                            for(let row of parsedJson){
                                if(!row){
                                    continue;
                                }
                                chartData.values.push(row.metric);
                                chartData.xLabels.push(row.tw);
                            }                        
                            self.$refs.totalRequestsBarChart.showChart(chartData);                            
                        });
                        
                    });
                },
                loadRequestsByStatusCodesData(){
                    var self = this;
                    var url = '../api/metrics?domainId='+this.domainId+'&metricType=3&groupBy=key&twStart=' + this.twStart + '&twEnd=' + this.twEnd;
                    window.fetch(url).then((response)=>{                        
                        response.json().then((parsedJson)=>{
                        console.log("parsedJson in loadRequestsByStatusCodesData");    
                        console.log(parsedJson);
                            let chartData = {
                                legends:[],
                                values:[]
                            };
                            for(let row of parsedJson){
                                if(!row){
                                    continue;
                                }
                                chartData.values.push({value:row.metric, name:row.keyValue});
                                chartData.legends.push(row.keyValue);
                            }                        
                            self.$refs.statusCodesPieChart.showChart(chartData);                            
                        });
                        
                    });
                },
                /**
                 * Initializes ECharts instances for each chart container.
                 * This should be called once when the component mounts.
                 */
                initCharts() {
                    // Get the DOM elements where charts will be rendered
                    const barChartDom = document.getElementById('barChart');
                    const pieChartDom = document.getElementById('pieChart');
                    const stackedLineChartDom = document.getElementById('stackedLineChart');

                    // Initialize ECharts instances
                    // Use `echarts.init(dom, theme)` if you have a custom theme
                    this.barChart = echarts.init(barChartDom);
                    this.pieChart = echarts.init(pieChartDom);
                    this.stackedLineChart = echarts.init(stackedLineChartDom);
                },

                /**
                 * Simulates fetching metrics data from a REST API.
                 * In a real application, you would replace this with an actual `fetch` or `axios` call.
                 */
                async fetchMetricsData() {
                    this.loading = true; // Set loading state to true
                    this.error = null;   // Clear any previous errors

                    try {
                        // Simulate an API call with a delay (e.g., 1.5 seconds)
                        const response = await new Promise(resolve => {
                            setTimeout(() => {
                                // Mock data structure for demonstration
                                resolve({
                                    barData: {
                                        months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                        sales: [120, 200, 150, 80, 70, 110, 130, 210, 160, 90, 75, 125]
                                    },
                                    pieData: [
                                        { value: 335, name: 'Electronics' },
                                        { value: 310, name: 'Apparel' },
                                        { value: 234, name: 'Home Goods' },
                                        { value: 135, name: 'Books' },
                                        { value: 154, name: 'Food & Beverage' },
                                        { value: 335, name: 'TVs' },
                                        { value: 310, name: 'Cars' },
                                        { value: 234, name: 'Laptops' },
                                        { value: 135, name: 'Meat' },
                                        { value: 154, name: 'Coke' },
                                        { value: 154, name: 'FB2' },
                                        { value: 335, name: 'TVs 2' },
                                        { value: 310, name: 'Cars 2' },
                                        { value: 234, name: 'Laptops 2' },
                                        { value: 135, name: 'Meat 2' },
                                        { value: 154, name: 'Coke  2' },
                                    ],
                                    stackedLineData: {
                                        quarters: ['Q1 2024', 'Q2 2024', 'Q3 2024', 'Q4 2024', 'Q1 2025', 'Q2 2025', 'Q3 2025'],
                                        productA: [120, 132, 101, 134, 90, 230, 210],
                                        productB: [220, 182, 191, 234, 290, 330, 310],
                                        productC: [150, 232, 201, 154, 190, 330, 350],
                                        productD: [820, 932, 901, 934, 1290, 1330, 1400]
                                    }
                                });
                            }, 500); // Simulate network delay
                        });

                        // Update each chart with the fetched data
                        this.updateBarChart(response.barData);
                        this.updatePieChart(response.pieData);
                        this.updateStackedLineChart(response.stackedLineData);

                    } catch (err) {
                        // Catch and display any errors during data fetching
                        console.error("Failed to fetch metrics:", err);
                        this.error = "Failed to load metrics data. Please check your network or API endpoint.";
                    } finally {
                        this.loading = false; // Set loading state to false
                    }
                },

                /**
                 * Configures and updates the Bar Chart.
                 * @param {object} data - Object containing months (categories) and sales (values).
                 */
                updateBarChart(data) {
                    const option = {
                        title: {
                            text: 'Monthly Sales Performance',
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
                            data: data.months,
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
                            name: 'Sales Amount ($)',
                            axisLabel: {
                                formatter: '${value}'
                            }
                        },
                        series: [
                            {
                                name: 'Sales',
                                type: 'bar',
                                data: data.sales,
                                itemStyle: {
                                    borderRadius: [5, 5, 0, 0], // Rounded corners on top of bars
                                    color: '#7209b7' /* new echarts.graphic.LinearGradient( // Gradient color
                                        0, 0, 0, 1,
                                        [
                                            { offset: 0, color: '#7209b7' },
                                            { offset: 1, color: '#3f37c9' }
                                        ]
                                    )
                                    */
                                },
                                emphasis: {
                                    itemStyle: {
                                        color: '#f72585' // Highlight color on hover
                                    }
                                },
                                barWidth: '60%' // Adjust bar width
                            }
                        ]
                    };
                    this.barChart.setOption(option);
                },

                /**
                 * Configures and updates the Pie Chart.
                 * @param {Array<object>} data - Array of objects with 'value' and 'name' properties.
                 */
                updatePieChart(data) {
                    const option = {
                        title: {
                            text: 'Revenue Distribution by Category',
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
                            data: data.map(item => item.name) // Extract names for legend
                        },
                        series: [
                            {
                                name: 'Revenue', // Series name for tooltip
                                type: 'pie',
                                radius: ['40%', '70%'], // Inner and outer radius for a donut chart effect
                                center: ['50%', '60%'], // Position of the chart center
                                data: data,
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
                        color: this.chartBgColors
                    };
                    this.pieChart.setOption(option);
                },

                /**
                 * Configures and updates the Stacked Line Chart.
                 * @param {object} data - Object containing quarters (categories) and multiple product series.
                 */
                updateStackedLineChart(data) {
                    const series = [];
                    // Dynamically create series from the data object
                    // Each key (except 'quarters') represents a product series
                    for (const key in data) {
                        if (key !== 'quarters') {
                            series.push({
                                name: key.charAt(0).toUpperCase() + key.slice(1), // Capitalize product name for display
                                type: 'line',
                                stack: 'Total', // All series will stack on top of each other
                                areaStyle: {},  // Fill the area below the line for a stacked area effect
                                emphasis: {
                                    focus: 'series' // Highlight the entire series on hover
                                },
                                data: data[key],
                                smooth: true, // Smooth the lines
                                symbol: 'none' // Hide symbols on line points
                            });
                        }
                    }

                    const option = {
                        color: this.chartBgColors
                        /*[
                            '#4CAF50', // Green for Product A
                            '#2196F3', // Blue for Product B
                            '#FFC107', // Amber for Product C
                            '#F44336'  // Red for Product D
                        ]*/,
                        title: {
                            text: 'Product Performance Over Time (Stacked)',
                            left: 'center',
                            textStyle: {
                                fontSize: 16,
                                fontWeight: 'bold'
                            }
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'cross', // Crosshairs for better data inspection
                                label: {
                                    backgroundColor: '#6a7985'
                                }
                            }
                        },
                        legend: {
                            data: series.map(s => s.name), // Use series names for legend
                            bottom: 10, // Position legend at the bottom
                            left: 'center'
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '15%', // Adjust bottom to make space for the legend
                            containLabel: true // Ensure labels are contained within the grid
                        },
                        xAxis: [
                            {
                                type: 'category',
                                boundaryGap: false, // Lines start from the axis
                                data: data.quarters,
                                axisLabel: {
                                    rotate: 30, // Rotate labels for better readability
                                    interval: 0 // Display all labels
                                }
                            }
                        ],
                        yAxis: [
                            {
                                type: 'value',
                                name: 'Value'
                            }
                        ],
                        series: series
                    };
                    this.stackedLineChart.setOption(option);
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
        // Mount the Vue application to the DOM element with id="app"
        app.mount('#app');
    </script>
</body>
</html>