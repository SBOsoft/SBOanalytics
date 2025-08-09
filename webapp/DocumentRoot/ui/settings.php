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

if(($_SESSION['adminSecretVerified'] ?? '') !== 'yes'){
    $showAdminSecretForm = true;
    if(!empty($_POST['adminSecret'])){
        $secretFromEnv = getenv('SBO_ADMIN_SECRET');
        if(!empty($secretFromEnv) && $secretFromEnv === $_POST['adminSecret']){
            $_SESSION['adminSecretVerified'] = 'yes';
            $_SESSION['adminCSRFToken'] = bin2hex(random_bytes(20));
            $showAdminSecretForm = false;
        }
    }
    if($showAdminSecretForm){
?>
<div class="alert alert-warning">
    Enter admin secret to access this page
</div>

<form class="mb-4 pb-4 col-10 col-md-6 col-lg-4 col-xl-3" action="settings" method="post">        
    <h1 class="h3 mb-3 fw-normal text-center">Please sign in</h1>    
    
    <div class="form-floating my-2">
        <input name="adminSecret" type="password" class="form-control" id="floatingPassword" placeholder="Admin secret">
        <label for="floatingPassword">Admin secret</label>
    </div>
    <div class="form-floating">
        <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
    </div>
</form>
<?php
    }
}
?>
    <div id="app" class="p-2">        
        <div class="row">
            <div class="col-md-6 p-2">
                <div class="d-flex justify-content-between align-items-center px-1">
                    <h3>Domains</h3>
                    <button v-on:click="addNewDomain" class="btn btn-sm btn-outline-primary">
                        Add
                    </button>
                </div>
                <div class="list-group">
                    <div class="list-group-item list-group-item-action" v-for="(row) in allDomains">
                        <b class="text-danger">{{ row.domainId }}</b> - 
                        {{ row.domainName }}
                        <button class="btn btn-sm btn-outline-primary border-0" title="Edit" v-on:click="editDomain(row)"><i class="bi bi-pencil-square"></i></button>
                        <div class="small">
                            Created: {{ row.created }}
                            {{ row.description }}
                            <br>
                            Metrics window size: {{ row.timeWindowSizeMinutes }}
                        </div>                        
                    </div>
                    <div v-if="!allDomains || allDomains.length<1">
                        No domains found
                    </div>
                </div>
            </div>
            <div class="col-md-6 p-2">
                <div class="d-flex justify-content-between align-items-center px-1">
                    <h3>Hosts</h3>
                    <button v-on:click="addNewHost" class="btn btn-sm btn-outline-primary">
                        Add
                    </button>
                </div>
                <div class="list-group">
                    <div class="list-group-item list-group-item-action" v-for="(row) in allHosts">
                        <b class="text-danger">{{ row.hostId }}</b> - 
                        {{ row.hostName }}
                        <button class="btn btn-sm btn-outline-primary border-0" title="Edit" v-on:click="editHost(row)"><i class="bi bi-pencil-square"></i></button>
                        <div class="small">
                            Created: {{ row.created }}
                            {{ row.description }}
                        </div>
                    </div>
                </div>
                <div v-if="!allDomains || allDomains.length<1">
                    No domains found
                </div>
            </div>
        </div>
        <div class="modal" tabindex="-1" id="editDomainModal" style="background-color: rgb(50, 50, 50, 0.7)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Domain details</h5>
                        <button type="button" class="btn-close" v-on:click="closeEditDomainModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="editDomainError" class="alert alert-danger">
                            {{ editDomainError }}
                        </div>
                        <form name="editDomainForm" id="editDomainForm" action="" onsubmit="return false;" class="form-floating" enctype="application/x-www-form-urlencoded">
                            <input type="hidden" name="csrfToken" v-model="csrfToken">
                            <input type="hidden" name="domainId" v-model="currentEditedDomain.domainId">
                            <div class="my-1" v-if="currentEditedDomain.domainId>0">
                                <label for="domainIdTxt" class="form-label">Domain id</label>                                
                                <input type="text" class="form-control" disabled v-model="currentEditedDomain.domainId" id="domainIdTxt">
                                <div class="form-text text-secondary"> <i class="bi bi-info-circle"></i> Domain id cannot be edited</div>
                            </div>
                            <div class="my-1" v-if="currentEditedDomain.domainId==0">
                                <label for="hostIdTxt" class="form-label">Domain id</label>                                
                                <div class="form-text text-secondary"> <i class="bi bi-info-circle"></i> Domain id will be auto-generated</div>
                            </div>
                            <div class="my-1">
                                <label for="domainNameEditTxt" class="form-label">Domain name</label>
                                <input type="text" class="form-control" name="domainName" id="domainNameEditTxt" placeholder="example.com" v-model="currentEditedDomain.domainName">                                
                            </div>
                            <div class="my-1">
                                <label for="domainDescriptionEditTxt" class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" id="domainDescriptionEditTxt" placeholder="Some information about this domain" v-model="currentEditedDomain.description">
                                
                            </div>
                            <div class="my-1">
                                <label for="timeWindowSizeMinutesEdit" class="form-label">Metrics time window size in minutes</label>
                                <select name="timeWindowSizeMinutes" id="timeWindowSizeMinutesEdit" v-model="currentEditedDomain.timeWindowSizeMinutes" class="form-control">
                                    <option value="1">1</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="30">30</option>
                                    <option value="60">60</option>
                                </select>
                                
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" v-on:click="closeEditDomainModal">Close</button>
                        <button type="button" class="btn btn-primary" v-on:click="saveDomain">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal" tabindex="-1" id="editHostModal" style="background-color: rgb(50, 50, 50, 0.7)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Host details</h5>
                        <button type="button" class="btn-close" v-on:click="closeEditHostModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="editHostError" class="alert alert-danger">
                            {{ editHostError }}
                        </div>
                        <form name="editHostForm" id="editHostForm" action="" onsubmit="return false;" class="form-floating" enctype="application/x-www-form-urlencoded">
                            <input type="hidden" name="csrfToken" v-model="csrfToken">
                            <input type="hidden" name="hostId" v-model="currentEditedHost.hostId">
                            <div class="my-1" v-if="currentEditedHost.hostId>0">
                                <label for="hostIdTxt" class="form-label">Host id</label>                                
                                <input type="text" class="form-control" disabled v-model="currentEditedHost.hostId" id="hostIdTxt">
                                <div class="form-text text-secondary"> <i class="bi bi-info-circle"></i> Host id cannot be edited</div>
                            </div>
                            <div class="my-1" v-if="currentEditedHost.hostId==0">
                                <label for="hostIdTxt" class="form-label">Host id</label>                                
                                <div class="form-text text-secondary"> <i class="bi bi-info-circle"></i> Host id will be auto-generated</div>
                            </div>
                            <div class="my-1">
                                <label for="hostNameEditTxt" class="form-label">Host name</label>
                                <input type="text" class="form-control" name="hostName" id="hostNameEditTxt" placeholder="server1" v-model="currentEditedHost.hostName">                                
                            </div>
                            <div class="my-1">
                                <label for="hostDescriptionEditTxt" class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" id="hostDescriptionEditTxt" placeholder="Some information about this host" v-model="currentEditedHost.description">
                                
                            </div>                            
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" v-on:click="closeEditHostModal">Close</button>
                        <button type="button" class="btn btn-primary" v-on:click="saveHost">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="alert alert-info">
            <ul>
                <li>
                    You can define hosts and domains here.
                </li>
                <li>
                    You <b>MUST</b> add a host here before setting up OS metrics collection and add the host id you obtain here to 
                    sbologp configuration.
                </li>
                <li>
                    Domains will be auto-created by sbologp, you don't need to create them before hand but you can do so if you like.
                </li>
            </ul>            
        </div>
    </div>
    <script>
        
        const app = Vue.createApp({
            components:{
                
            },
            data() {
                return {        
                    csrfToken: <?php echo json_encode($_SESSION['adminCSRFToken'] ?? '');?>,
                    allHosts:{},
                    moreHosts:false,
                    dataLimit:50,
                    hostsPage:1,
                    currentEditedHost:{},
                    allDomains:{},
                    moreDomains:false,
                    domainsPage: 1,
                    editDomainError: '',
                    currentEditedDomain:{domainId:0, domainName:'', description:'', timeWindowSizeMinutesEdit:10}
                };
            },
            mounted() {
            },            
            beforeMount() {
                this.loadAllHosts();
                this.loadAllDomains();
            },
            
            // Methods for the component
            methods: {
                
                loadAllHosts(){
                    var self = this;
                    window.fetch('../api/hosts').then((response)=>{
                        response.json().then((parsedJson)=>{
                            self.allHosts = {};
                            self.moreHosts = parsedJson.hasMoreResults;
                            for(let i in parsedJson){
                                self.allHosts[parsedJson[i].hostId] = parsedJson[i];
                            }
                        });
                    });
                },
                loadAllDomains(){
                    var self = this;
                    window.fetch('../api/domains').then((response)=>{
                        response.json().then((parsedJson)=>{
                            self.allDomains = {};
                            self.moreDomains = parsedJson.hasMoreResults;
                            for(let i in parsedJson){
                                self.allDomains[parsedJson[i].domainId] = parsedJson[i];
                            }
                        });
                    });
                },
                closeEditDomainModal(){
                    let m = document.getElementById('editDomainModal');
                    m.style.display='none';
                },
                showEditDomainModal(){
                    let m = document.getElementById('editDomainModal');
                    m.style.display='block';
                    m.style.position='fixed';
                    m.style.zIndex='99';
                    m.style.left=0;
                    m.style.top=0;
                    this.editDomainError = '';
                },
                addNewDomain(){
                    this.currentEditedDomain = {domainId:0, domainName:'', description:'', timeWindowSizeMinutesEdit:10}
                    this.showEditDomainModal();
                },
                editDomain(domainInfo){
                    this.currentEditedDomain = {...domainInfo};
                    this.showEditDomainModal();
                },
                saveDomain(){
                    var self = this;
                    var form = document.getElementById('editDomainForm');
                    const formData = new FormData(form);
                    const response = fetch('../api/admin-domain-save', {
                        method: 'POST',
                        body: formData,
                        //headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(response=>{
                       if (response.ok) {
                           self.closeEditDomainModal();
                           self.loadAllDomains();
                       }
                       else{
                           response.json().then(body=>{
                               self.editDomainError = body.error + ': ' + body.description;
                           });
                       }
                    });
                },
                closeEditHostModal(){
                    let m = document.getElementById('editHostModal');
                    m.style.display='none';
                },
                showEditHostModal(){
                    let m = document.getElementById('editHostModal');
                    m.style.display='block';
                    m.style.position='fixed';
                    m.style.zIndex='99';
                    m.style.left=0;
                    m.style.top=0;
                    this.editHostError = '';
                },
                addNewHost(){
                    this.currentEditedHost = {hostId:0, hostName:'', description:''}
                    this.showEditHostModal();
                },
                editHost(hostInfo){
                    this.currentEditedHost = {...hostInfo};
                    this.showEditHostModal();
                },
                saveHost(){
                    var self = this;
                    var form = document.getElementById('editHostForm');
                    const formData = new FormData(form);
                    const response = fetch('../api/admin-host-save', {
                        method: 'POST',
                        body: formData,
                        //headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(response=>{
                       if (response.ok) {
                           self.closeEditHostModal();
                           self.loadAllHosts();
                       }
                       else{
                           response.json().then(body=>{
                               self.editHostError = body.error + ': ' + body.description;
                           });
                       }
                    });
                }
            }
        });

        app.component('sbo-linechart', SBOLineChart);

        app.mount('#app');
    </script>
