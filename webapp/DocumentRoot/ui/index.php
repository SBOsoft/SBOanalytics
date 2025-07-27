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

define('SBO_FILE_INCLUDED_PROPERLY', true);
include('../common.php');

session_start();

$action=($_REQUEST['act'] ?? null);
if($action!=='auth-form' && $action!=='auth-submit'){
    SBO_CheckIsAuthenticated(false);
}

switch($action){
    case 'auth-form':
        $pageTitle='Please sign in first';
        break;
    case 'auth-submit':        
        include('auth-submit.php');
        return;     //return here, we don't need html etc, just validating submitted values
    case 'logs':
        $pageTitle='Log viewer';
        
        break;
    case 'dashboard':
    default:
        $pageTitle='Metrics Dashboard';
        break;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SBO_HtmlEncode($pageTitle);?></title>
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
        .sbo-status400 td{
            color:var(--bs-secondary) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sbo-nav shadow-sm" data-bs-theme="dark">
            <div class="container-fluid">
                <div class="text-light">
                <a class="navbar-brand me-2" href="https://www.sbosoft.net/sboanalytics.html" title="SBOanalytics free web server analytics" target="_blank">SBOanalytics</a>

                <a class="text-white me-2" href="dashboard" title="Metrics dashboard">Metrics</a>

                <a class="text-white" href="logs" title="Log viewer">Logs</a>

                </div>
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
    <?php
    switch($action){
        case 'auth-form':
            include('auth-form.php');
            break;
        case 'auth-submit':
            include('auth-submit.php');
            break;        
        case 'logs':
            include('log-viewer.php');
            break;        
        case 'dashboard':
        default:
            include('dashboard.php');
            break;
    }
    ?>
    <footer class="pt-4 my-md-5 pt-md-5 border-top border-primary-subtle">
        <div class="container-fluid container-lg">
            <div class="row">
                <div class="col-12 col-md">
                    <div class="my-2 text-center text-secondary">
                        SBOanalytics by 
                        <a href="https://www.sbosoft.net/" target="_blank" rel="noopener">SBOSOFT</a>. 
                        <?php
                        if(file_exists('../version.txt')){
                            $versionStr = file_get_contents('../version.txt');
                            echo ' Version: ';
                            echo SBO_HtmlEncode($versionStr);
                        }
                        ?>
                        <br>
                        See 
                        <a href="https://github.com/SBOsoft/SBOanalytics" title="SBOanalytics (frontend) at github"><i class="bi bi-github"></i> SBOanalytics</a>
                        and
                        <a href="https://github.com/SBOsoft/SBOLogProcessor" title="Log processor (backend) at github"><i class="bi bi-github"></i> SBOLogProcessor</a>
                        for latest updates and documentation
                    </div>                   
                </div>                
            </div>
        </div>
    </footer>
</body>
</html>