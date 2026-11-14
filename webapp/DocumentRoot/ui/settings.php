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
    //the secret is verified by admin-auth-submit.php (before any output), which redirects back here
    $adminSecretConfigured = !empty(getenv('SBO_ADMIN_SECRET'));
    $adminLockedOut = ($_SESSION['adminSecretLockedUntil'] ?? 0) > time();
    $adminError = $_GET['adminError'] ?? '';
?>
<div class="container d-flex justify-content-center align-items-center">
    <div class="pt-4">
<?php if(!$adminSecretConfigured){ ?>
        <div class="alert alert-warning">
            Administration is disabled because <code>SBO_ADMIN_SECRET</code> is not configured on the server.
        </div>
<?php } else { ?>
        <form class="mb-4 pb-4" action="admin-auth-submit" method="post">
            <h1 class="h3 mb-3 fw-normal text-center">Please sign in</h1>
<?php if($adminLockedOut){ ?>
            <div class="alert alert-danger">
                Too many failed attempts. Please try again later.
            </div>
<?php } else if($adminError === 'invalid'){ ?>
            <div class="alert alert-danger">
                Invalid admin secret
            </div>
<?php } else { ?>
            <div class="alert alert-warning">
                Enter admin secret to access this page
            </div>
<?php } ?>
            <div class="form-floating my-2">
                <input name="adminSecret" type="password" class="form-control" id="floatingPassword" placeholder="Admin secret">
                <label for="floatingPassword">Admin secret</label>
            </div>
            <div class="form-floating">
                <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
            </div>
        </form>
<?php } ?>
    </div>
</div>
<?php
    return;
}   //if !adminSecretVerified
?>
    <div id="app" class="p-2">
        <div v-if="loadError" class="alert alert-danger">{{ loadError }}</div>
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
                    <div v-if="domainsLoaded && Object.keys(allDomains).length===0" class="list-group-item text-secondary">
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
                    <div v-if="hostsLoaded && Object.keys(allHosts).length===0" class="list-group-item text-secondary">
                        No hosts found
                    </div>
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
                        <button type="button" class="btn btn-primary" v-on:click="saveDomain" v-bind:disabled="savingDomain">Save changes</button>
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
                        <button type="button" class="btn btn-primary" v-on:click="saveHost" v-bind:disabled="savingHost">Save changes</button>
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
                    loadError: '',
                    allHosts:{},
                    hostsLoaded:false,
                    currentEditedHost:{},
                    editHostError: '',
                    savingHost:false,
                    allDomains:{},
                    domainsLoaded:false,
                    editDomainError: '',
                    savingDomain:false,
                    currentEditedDomain:{domainId:0, domainName:'', description:'', timeWindowSizeMinutes:10}
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
                    SBO_FetchList('../api/hosts', 'hostId').then((hosts)=>{
                        self.allHosts = hosts;
                        self.hostsLoaded = true;
                    }).catch((err)=>{
                        self.loadError = 'Could not load hosts: ' + err.message;
                    });
                },
                loadAllDomains(){
                    var self = this;
                    SBO_FetchList('../api/domains', 'domainId').then((domains)=>{
                        self.allDomains = domains;
                        self.domainsLoaded = true;
                    }).catch((err)=>{
                        self.loadError = 'Could not load domains: ' + err.message;
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
                    this.currentEditedDomain = {domainId:0, domainName:'', description:'', timeWindowSizeMinutes:10}
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
                    this.savingDomain = true;
                    this.editDomainError = '';
                    SBO_FetchJson('../api/admin-domain-save', {
                        method: 'POST',
                        body: formData
                    }).then(()=>{
                        self.closeEditDomainModal();
                        self.loadAllDomains();
                    }).catch((err)=>{
                        self.editDomainError = err.message;
                    }).finally(()=>{
                        self.savingDomain = false;
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
                    this.savingHost = true;
                    this.editHostError = '';
                    SBO_FetchJson('../api/admin-host-save', {
                        method: 'POST',
                        body: formData
                    }).then(()=>{
                        self.closeEditHostModal();
                        self.loadAllHosts();
                    }).catch((err)=>{
                        self.editHostError = err.message;
                    }).finally(()=>{
                        self.savingHost = false;
                    });
                }
            }
        });

        app.mount('#app');
    </script>
