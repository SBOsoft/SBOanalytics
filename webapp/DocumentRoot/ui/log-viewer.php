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
if (!defined('SBO_FILE_INCLUDED_PROPERLY')) {
    http_response_code(404);
    exit;
}
$domainId = (int) filter_input(INPUT_GET, 'domainId', FILTER_SANITIZE_NUMBER_INT);
$keyName = filter_input(INPUT_GET, 'keyName', FILTER_VALIDATE_REGEXP, array('default' => null, 'options' => array('regexp' => '/^([a-zA-Z0-9]+)$/')));
$keyValue = filter_input(INPUT_GET, 'keyValue', FILTER_UNSAFE_RAW, array('default' => null, /* 'options'=>array('regexp'=>'/^([\/a-zA-Z0-9 ]+)$/') */));

$twStartTS = filter_input(INPUT_GET, 'twStartTS', FILTER_VALIDATE_REGEXP, array('default' => null, 'options' => array('regexp' => '/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})$/')));
$twEndTS = filter_input(INPUT_GET, 'twEndTS', FILTER_VALIDATE_REGEXP, array('default' => null, 'options' => array('regexp' => '/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})$/')));
?>
<div id="app">        
    <div class="px-4 py-2 border-bottom bg-primary-subtle">
        <form action="" class="form form-sm" onsubmit="return false;">
            <div class="row align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Domain</label>
                    <select name="selectedDomain" id="selectedDomainSelect" v-model="domainId" class="form-select">
                        <option v-for="(row) in allDomains" v-bind:value="row.domainId">{{ row.domainName}}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label">Between</label>
                    <input type="datetime-local" id="twStartInput" v-model="twStart" class="form-control">
                </div>
                <div class="col-auto">
                    <label class="form-label">and</label>
                    <input type="datetime-local" id="twEndInput" v-model="twEnd" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Filter By</label>
                    <select name="keyName" id="groupBySelect" v-model="keyName" class="form-select">
                        <option value="">--None--</option>
                        <option value="clientIP">Client IP</option>
                        <option value="remoteUser">User</option>
                        <option value="method">Method</option>
                        <option value="basePath">Path</option>
                        <option value="statusCode">Status</option>
                        <option value="uaOS">Client OS</option>
                        <option value="uaFamily">User agent family</option>
                        <option value="deviceType">Device type</option>
                        <option value="isHuman">Is human</option>
                        <option value="requestIntent">Intent</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Value</label>
                    <input type="text" id="keyValue" v-model="keyValue" class="form-control">
                </div>
                <div class="col-auto mt-2">
                    <button class="btn btn-primary" v-on:click="loadLogs" type="button">Show Logs</button>
                </div>
            </div>

        </form>
    </div>

    <div class="container-fluid m-0">
        <div v-if="error" class="alert alert-danger text-center mx-auto my-5" style="max-width: 600px;" role="alert">
            {{ error }}
        </div>
        <div style="min-height:30vh;">
            <sbo-logsview ref="logsView" elem-id="sboRawLogsView"></sbo-logsview>
        </div>
        <div class="text-secondary small my-2">
            <ul>
                <li>Please note that depending on your configuration, some logs might not have been saved which may lead to differences between metrics and number of log entries displayed on this page</li>
                <li>Only the first 100 characters of user agent and paths are saved</li>
            </ul>
        </div>
    </div>        
</div>

<script>
    const app = Vue.createApp({
        components: {
            SBOLogsView
        },
        data() {
            return {
                allDomains: {},
                domainId: <?php echo $domainId; ?>,
                error: 'Select domain and period and click Show Logs to start',
                twStart:<?php
$defaultTwStartValue = '';
if ($twStartTS) {
    $defaultTwStartValue = substr($twStartTS, 0, 16);
} 
else {
    $yesterday = strtotime("-1 day");
    $defaultTwStartValue = date('Y-m-d H:i', $yesterday);
}
echo SBO_JsonEncode($defaultTwStartValue);
?>,
                twEnd:<?php
$defaultTwEndValue = '';
if ($twEndTS) {
    $defaultTwEndValue = substr($twEndTS, 0, 16);
} 
else {
    $defaultTwEndValue = date('Y-m-d H:i');
}
echo SBO_JsonEncode($defaultTwEndValue);
?>,
                keyName:<?php echo SBO_JsonEncode($keyName); ?>,
                keyValue:<?php echo SBO_JsonEncode($keyValue); ?>
            };
        },
        mounted() {            
            if (this.domainId > 0) {
                this.error = '';
                this.loadLogs();
            }
        },
        beforeMount() {
            this.loadAllDomains();
        },
        methods: {
            loadLogs() {
                this.error = '';
                this.$refs.logsView.initParams(this.domainId, this.keyName, this.keyValue, this.twStart, this.twEnd, 20, 'Logs');
                this.$refs.logsView.goToPage(1);
            },
            loadAllDomains() {
                var self = this;
                window.fetch('../api/domains').then((response) => {
                    response.json().then((parsedJson) => {
                        self.allDomains = {};
                        for (let i in parsedJson) {
                            self.allDomains[parsedJson[i].domainId] = parsedJson[i];
                        }
                    });
                });
            },

        }
    });

    app.component('sbo-logsview', SBOLogsView);

    app.mount('#app');
</script>
