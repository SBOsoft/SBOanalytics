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
<div class="d-flex justify-content-center align-items-center vh-100 row">
    <form class="mb-4 pb-4 col-10 col-md-6 col-lg-4 col-xl-3" action="auth-submit" method="POST">        
        <h1 class="h3 mb-3 fw-normal text-center">Please sign in</h1>
        <?php
        if($_REQUEST['authError'] ?? 0){
        ?>
            <div class="alert alert-warning">Log in failed</div>
        <?php
        }
        ?>
        <div class="form-floating">
            <input name="username" type="text" class="form-control" id="floatingInput" placeholder="yourusername">
            <label for="floatingInput">Username</label>
        </div>
        <div class="form-floating my-2">
            <input name="password" type="password" class="form-control" id="floatingPassword" placeholder="Password">
            <label for="floatingPassword">Password</label>
        </div>
        <div class="form-floating">
            <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
        </div>
        <div class="text-secondary text-center p-4">            
            <a href="https://www.sbosoft.net/sboanalytics" target="_blank" class="me-4">SBOanalytics</a>
            <a href="https://github.com/SBOsoft/SBOanalytics" target="_blank">@Github</a>            
        </div>
    </form>
</div>