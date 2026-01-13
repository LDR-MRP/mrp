<?php
  headerAdmin($data);
  getModal('modalPerfil',$data);

  
 ?>

       <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="position-relative mx-n4 mt-n4">
                        <div class="profile-wid-bg profile-setting-img">
                            <img src="<?= media();?>/images/portada.png" class="profile-wid-img" alt="">
                            <div class="overlay-content">
                                <div class="text-end p-3">
                                    <div class="p-0 ms-auto rounded-circle profile-photo-edit">
                                        <input id="profile-foreground-img-file-input" type="file" class="profile-foreground-img-file-input">
                                        <label for="profile-foreground-img-file-input" class="profile-photo-edit btn btn-light">
                                            <i class="ri-image-edit-line align-bottom me-1"></i> Change Cover
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?= $avatar = $data['usuario']['avatar_file'] ?>

                    <div class="row">
                        <div class="col-xxl-3">
                            <div class="card mt-n5">
                                <div class="card-body p-4">
                                    <div class="text-center">
                                        <div class="profile-user position-relative d-inline-block mx-auto  mb-4">
                                            <img src="<?= media();?>/avatars/<?= $avatar; ?>" class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                                <!-- <input id="profile-img-file-input" type="file" class="profile-img-file-input"> -->
                                                <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                                    <!-- <span class="avatar-title rounded-circle bg-light text-body">
                                                        <i class="ri-camera-fill"></i>
                                                    </span> -->
                                                </label>
                                            </div>
                                        </div>

                                    
                                        <h5 class="fs-16 mb-1"><?= $_SESSION['userData']['nombres'].' '.$_SESSION['userData']['apellidos']; ?></h5>
                                        <p class="text-muted mb-0"><?= $_SESSION['userData']['nombrerol']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <!--end card-->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-5">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-0">Completa tu perfil</h5>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="javascript:void(0);" class="badge bg-light text-secondary fs-12"><i class="ri-edit-box-line align-bottom me-1"></i> Edit</a>
                                        </div>
                                    </div>
                                    <div class="progress animated-progress custom-progress progress-label">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">
                                            <div class="label">30%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                     
                            <!--end card-->
                        </div>
                        <!--end col-->
                        <div class="col-xxl-9">
                            <div class="card mt-xxl-n5">
                                <div class="card-header">
                                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                                <i class="fas fa-home"></i>
                                                Datos Personales
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#changePassword" role="tab">
                                                <i class="far fa-user"></i>
                                                Cambiar la contraseña
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#experience" role="tab">
                                                <i class="far fa-envelope"></i>
                                                Experiencia
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#privacy" role="tab">
                                                <i class="far fa-envelope"></i>
                                                Política de privacidad
                                            </a>
                                        </li>


                                        <li class="nav-item">
  <a class="nav-link" data-bs-toggle="tab" href="#tabAvatar" role="tab">
    <i class="ri-user-smile-line"></i>
    Avatar / Foto
  </a>
</li>

                                    </ul>
                                </div>
                                <div class="card-body p-4">
                                    <div class="tab-content">
                                        <div class="tab-pane" id="personalDetails" role="tabpanel">
                                            <form action="javascript:void(0);">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label for="firstnameInput" class="form-label">Nombres</label>
                                                            <input type="text" class="form-control" id="firstnameInput" placeholder="Enter your firstname" value="<?= $_SESSION['userData']['nombres']; ?>">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label for="lastnameInput" class="form-label">Apellidos
                                                                </label>
                                                            <input type="text" class="form-control" id="lastnameInput" placeholder="Enter your lastname" value="<?= $_SESSION['userData']['apellidos']; ?>">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label for="phonenumberInput" class="form-label">Número de teléfono</label>
                                                            <input type="text" class="form-control" id="phonenumberInput" placeholder="Enter your phone number" value="<?= $_SESSION['userData']['telefono']; ?>">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label for="emailInput" class="form-label">Dirección de correo electrónico</label>
                                                            <input type="email" class="form-control" id="emailInput" placeholder="Enter your email" value="<?= $_SESSION['userData']['email_user']; ?>">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <label for="JoiningdatInput" class="form-label">Fecha de ingreso</label>
                                                            <input type="text" class="form-control" data-provider="flatpickr" id="JoiningdatInput" data-date-format="d M, Y" data-deafult-date="24 Nov, 2021" placeholder="Select date" />
                                                        </div>
                                                    </div>
                                                    <!--end col-->



                                                    <!--end col-->
                                                    <div class="col-lg-12">
                                                        <div class="mb-3 pb-2">
                                                            <label for="exampleFormControlTextarea" class="form-label">Description</label>
                                                            <textarea class="form-control" id="exampleFormControlTextarea" placeholder="Enter your description" rows="3">Hi I'm Anna Adame,It will be as simple as Occidental; in fact, it will be Occidental. To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is European languages are members of the same family.</textarea>
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-12">
                                                        <div class="hstack gap-2 justify-content-end">
                                                            <button type="submit" class="btn btn-primary">Updates</button>
                                                            <button type="button" class="btn btn-soft-secondary">Cancel</button>
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                </div>
                                                <!--end row-->
                                            </form>
                                        </div>
                                        <!--end tab-pane-->
                                        <div class="tab-pane active" id="changePassword" role="tabpanel">
                                            <form action="javascript:void(0);">
                                                <div class="row g-2">
                                                    <div class="col-lg-4">
                                                        <div>
                                                            <label for="oldpasswordInput" class="form-label">Contraseña anterior*</label>
                                                            <input type="password" class="form-control" id="oldpasswordInput" placeholder="Enter current password">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-4">
                                                        <div>
                                                            <label for="newpasswordInput" class="form-label">Nueva contraseña*</label>
                                                            <input type="password" class="form-control" id="newpasswordInput" placeholder="Enter new password">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-lg-4">
                                                        <div>
                                                            <label for="confirmpasswordInput" class="form-label">Confirmar Contraseña*</label>
                                                            <input type="password" class="form-control" id="confirmpasswordInput" placeholder="Confirm password">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <!-- <div class="col-lg-12">
                                                        <div class="mb-3">
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline">Forgot
                                                                Password ?</a>
                                                        </div>
                                                    </div> -->
                                                    <!--end col-->
                                                    <div class="col-lg-12">
                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                </div>
                                                <!--end row-->
                                            </form>
                                            <div class="mt-4 mb-3 border-bottom pb-2">
                                                <div class="float-end">
                                                    <a href="javascript:void(0);" class="link-secondary">All Logout</a>
                                                </div>
                                                <h5 class="card-title">Historial de inicio de sesión</h5>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 avatar-sm">
                                                    <div class="avatar-title bg-light text-primary rounded-3 fs-18">
                                                        <i class="ri-smartphone-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6>iPhone 12 Pro</h6>
                                                    <p class="text-muted mb-0">Los Angeles, United States - March 16 at
                                                        2:47PM</p>
                                                </div>
                                                <div>
                                                    <a href="javascript:void(0);" class="link-secondary">Logout</a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 avatar-sm">
                                                    <div class="avatar-title bg-light text-primary rounded-3 fs-18">
                                                        <i class="ri-tablet-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6>Apple iPad Pro</h6>
                                                    <p class="text-muted mb-0">Washington, United States - November 06
                                                        at 10:43AM</p>
                                                </div>
                                                <div>
                                                    <a href="javascript:void(0);" class="link-secondary">Logout</a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 avatar-sm">
                                                    <div class="avatar-title bg-light text-primary rounded-3 fs-18">
                                                        <i class="ri-smartphone-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6>Galaxy S21 Ultra 5G</h6>
                                                    <p class="text-muted mb-0">Conneticut, United States - June 12 at
                                                        3:24PM</p>
                                                </div>
                                                <div>
                                                    <a href="javascript:void(0);" class="link-secondary">Logout</a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 avatar-sm">
                                                    <div class="avatar-title bg-light text-primary rounded-3 fs-18">
                                                        <i class="ri-macbook-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6>Dell Inspiron 14</h6>
                                                    <p class="text-muted mb-0">Phoenix, United States - July 26 at
                                                        8:10AM</p>
                                                </div>
                                                <div>
                                                    <a href="javascript:void(0);" class="link-secondary">Logout</a>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end tab-pane-->
                                        <div class="tab-pane" id="experience" role="tabpanel">
                                            <form>
                                                <div id="newlink">
                                                    <div id="1">
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="mb-3">
                                                                    <label for="jobTitle" class="form-label">Job
                                                                        Title</label>
                                                                    <input type="text" class="form-control" id="jobTitle" placeholder="Job title" value="Lead Designer / Developer">
                                                                </div>
                                                            </div>
                                                            <!--end col-->
                                                            <div class="col-lg-6">
                                                                <div class="mb-3">
                                                                    <label for="companyName" class="form-label">Company
                                                                        Name</label>
                                                                    <input type="text" class="form-control" id="companyName" placeholder="Company name" value="Themesbrand">
                                                                </div>
                                                            </div>
                                                            <!--end col-->
                                                            <div class="col-lg-6">
                                                                <div class="mb-3">
                                                                    <label for="experienceYear" class="form-label">Experience Years</label>
                                                                    <div class="row">
                                                                        <div class="col-lg-5">
                                                                            <select class="form-control" data-choices data-choices-search-false name="experienceYear" id="experienceYear">
                                                                                <option value="">Select years</option>
                                                                                <option value="Choice 1">2001</option>
                                                                                <option value="Choice 2">2002</option>
                                                                                <option value="Choice 3">2003</option>
                                                                                <option value="Choice 4">2004</option>
                                                                                <option value="Choice 5">2005</option>
                                                                                <option value="Choice 6">2006</option>
                                                                                <option value="Choice 7">2007</option>
                                                                                <option value="Choice 8">2008</option>
                                                                                <option value="Choice 9">2009</option>
                                                                                <option value="Choice 10">2010</option>
                                                                                <option value="Choice 11">2011</option>
                                                                                <option value="Choice 12">2012</option>
                                                                                <option value="Choice 13">2013</option>
                                                                                <option value="Choice 14">2014</option>
                                                                                <option value="Choice 15">2015</option>
                                                                                <option value="Choice 16">2016</option>
                                                                                <option value="Choice 17" selected>2017
                                                                                </option>
                                                                                <option value="Choice 18">2018</option>
                                                                                <option value="Choice 19">2019</option>
                                                                                <option value="Choice 20">2020</option>
                                                                                <option value="Choice 21">2021</option>
                                                                                <option value="Choice 22">2022</option>
                                                                            </select>
                                                                        </div>
                                                                        <!--end col-->
                                                                        <div class="col-auto align-self-center">
                                                                            to
                                                                        </div>
                                                                        <!--end col-->
                                                                        <div class="col-lg-5">
                                                                            <select class="form-control" data-choices data-choices-search-false name="choices-single-default2">
                                                                                <option value="">Select years</option>
                                                                                <option value="Choice 1">2001</option>
                                                                                <option value="Choice 2">2002</option>
                                                                                <option value="Choice 3">2003</option>
                                                                                <option value="Choice 4">2004</option>
                                                                                <option value="Choice 5">2005</option>
                                                                                <option value="Choice 6">2006</option>
                                                                                <option value="Choice 7">2007</option>
                                                                                <option value="Choice 8">2008</option>
                                                                                <option value="Choice 9">2009</option>
                                                                                <option value="Choice 10">2010</option>
                                                                                <option value="Choice 11">2011</option>
                                                                                <option value="Choice 12">2012</option>
                                                                                <option value="Choice 13">2013</option>
                                                                                <option value="Choice 14">2014</option>
                                                                                <option value="Choice 15">2015</option>
                                                                                <option value="Choice 16">2016</option>
                                                                                <option value="Choice 17">2017</option>
                                                                                <option value="Choice 18">2018</option>
                                                                                <option value="Choice 19">2019</option>
                                                                                <option value="Choice 20" selected>2020
                                                                                </option>
                                                                                <option value="Choice 21">2021</option>
                                                                                <option value="Choice 22">2022</option>
                                                                            </select>
                                                                        </div>
                                                                        <!--end col-->
                                                                    </div>
                                                                    <!--end row-->
                                                                </div>
                                                            </div>
                                                            <!--end col-->
                                                            <div class="col-lg-12">
                                                                <div class="mb-3">
                                                                    <label for="jobDescription" class="form-label">Job
                                                                        Description</label>
                                                                    <textarea class="form-control" id="jobDescription" rows="3" placeholder="Enter description">You always want to make sure that your fonts work well together and try to limit the number of fonts you use to three or less. Experiment and play around with the fonts that you already have in the software you're working with reputable font websites. </textarea>
                                                                </div>
                                                            </div>
                                                            <!--end col-->
                                                            <div class="hstack gap-2 justify-content-end">
                                                                <a class="btn btn-success" href="javascript:deleteEl(1)">Delete</a>
                                                            </div>
                                                        </div>
                                                        <!--end row-->
                                                    </div>
                                                </div>
                                                <div id="newForm" style="display: none;">

                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="hstack gap-2">
                                                        <button type="submit" class="btn btn-success">Update</button>
                                                        <a href="javascript:new_link()" class="btn btn-primary">Add
                                                            New</a>
                                                    </div>
                                                </div>
                                                <!--end col-->
                                            </form>
                                        </div>
                                        <!--end tab-pane-->
                                        <div class="tab-pane" id="privacy" role="tabpanel">
                                            <div class="mb-4 pb-2">
                                                <h5 class="card-title text-decoration-underline mb-3">Security:</h5>
                                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-14 mb-1">Two-factor Authentication</h6>
                                                        <p class="text-muted">Two-factor authentication is an enhanced
                                                            security meansur. Once enabled, you'll be required to give
                                                            two types of identification when you log into Google
                                                            Authentication and SMS are Supported.</p>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-sm-3">
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Enable Two-facor
                                                            Authentication</a>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0 mt-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-14 mb-1">Secondary Verification</h6>
                                                        <p class="text-muted">The first factor is a password and the
                                                            second commonly includes a text with a code sent to your
                                                            smartphone, or biometrics using your fingerprint, face, or
                                                            retina.</p>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-sm-3">
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Set
                                                            up secondary method</a>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row mb-4 mb-sm-0 mt-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-14 mb-1">Backup Codes</h6>
                                                        <p class="text-muted mb-sm-0">A backup code is automatically
                                                            generated for you when you turn on two-factor authentication
                                                            through your iOS or Android Twitter app. You can also
                                                            generate a backup code on twitter.com.</p>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-sm-3">
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary">Generate backup codes</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <h5 class="card-title text-decoration-underline mb-3">Application
                                                    Notifications:</h5>
                                                <ul class="list-unstyled mb-0">
                                                    <li class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <label for="directMessage" class="form-check-label fs-14">Direct messages</label>
                                                            <p class="text-muted">Messages from people you follow</p>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="directMessage" checked />
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <li class="d-flex mt-2">
                                                        <div class="flex-grow-1">
                                                            <label class="form-check-label fs-14" for="desktopNotification">
                                                                Show desktop notifications
                                                            </label>
                                                            <p class="text-muted">Choose the option you want as your
                                                                default setting. Block a site: Next to "Not allowed to
                                                                send notifications," click Add.</p>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="desktopNotification" checked />
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <li class="d-flex mt-2">
                                                        <div class="flex-grow-1">
                                                            <label class="form-check-label fs-14" for="emailNotification">
                                                                Show email notifications
                                                            </label>
                                                            <p class="text-muted"> Under Settings, choose Notifications.
                                                                Under Select an account, choose the account to enable
                                                                notifications for. </p>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="emailNotification" />
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <li class="d-flex mt-2">
                                                        <div class="flex-grow-1">
                                                            <label class="form-check-label fs-14" for="chatNotification">
                                                                Show chat notifications
                                                            </label>
                                                            <p class="text-muted">To prevent duplicate mobile
                                                                notifications from the Gmail and Chat apps, in settings,
                                                                turn off Chat notifications.</p>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="chatNotification" />
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <li class="d-flex mt-2">
                                                        <div class="flex-grow-1">
                                                            <label class="form-check-label fs-14" for="purchaesNotification">
                                                                Show purchase notifications
                                                            </label>
                                                            <p class="text-muted">Get real-time purchase alerts to
                                                                protect yourself from fraudulent charges.</p>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="purchaesNotification" />
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <h5 class="card-title text-decoration-underline mb-3">Delete This
                                                    Account:</h5>
                                                <p class="text-muted">Go to the Data & Privacy section of your profile
                                                    Account. Scroll to "Your data & privacy options." Delete your
                                                    Profile Account. Follow the instructions to delete your account :
                                                </p>
                                                <div>
                                                    <input type="password" class="form-control" id="passwordInput" placeholder="Enter your password" value="make@321654987" style="max-width: 265px;">
                                                </div>
                                                <div class="hstack gap-2 mt-3">
                                                    <a href="javascript:void(0);" class="btn btn-soft-primary">Close &
                                                        Delete This Account</a>
                                                    <a href="javascript:void(0);" class="btn btn-light">Cancel</a>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end tab-pane-->

<!-- =========================
     TAB: AVATAR / FOTO
========================== -->
<div class="tab-pane" id="tabAvatar" role="tabpanel">

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card border">
        <div class="card-header">
          <h6 class="mb-0"><i class="ri-user-3-line me-1"></i> Crear avatar</h6>
        </div>
        <div class="card-body">


       

        <input type="hidden" id="usuarioid" name="usuarioid" value=" <?= $_SESSION['userData']['idusuario'] ?>">

        <input type="hidden" id="avatar_seed" name="avatar_seed" value="">
<input type="hidden" id="avatar_svg"  name="avatar_svg"  value="">


          <div class="alert alert-info py-2">
            <i class="ri-information-line me-1"></i>
            Elige <b>Hombre / Mujer / Unisex</b> y personaliza tu avatar. Al guardar, se asignará a tu perfil.
          </div>

          <!-- tipo -->
          <label class="form-label fw-semibold">Tipo</label>
          <div class="btn-group w-100 mb-3" role="group">
            <input type="radio" class="btn-check" name="mrpGender" id="mrpMale" value="male" autocomplete="off">
            <label class="btn btn-outline-dark" for="mrpMale"><i class="ri-men-line me-1"></i> Hombre</label>

            <input type="radio" class="btn-check" name="mrpGender" id="mrpFemale" value="female" autocomplete="off">
            <label class="btn btn-outline-dark" for="mrpFemale"><i class="ri-women-line me-1"></i> Mujer</label>

            <input type="radio" class="btn-check" name="mrpGender" id="mrpUnisex" value="unisex" autocomplete="off" checked>
            <label class="btn btn-outline-dark" for="mrpUnisex"><i class="ri-user-line me-1"></i> Unisex</label>
          </div>

          <!-- seed -->
          <label class="form-label fw-semibold">Seed</label>
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="ri-fingerprint-line"></i></span>
            <input type="text" id="mrpSeed" class="form-control" placeholder="Ej: <?= $_SESSION['userData']['email_user'] ?? 'usuario'; ?>">
            <button class="btn btn-outline-secondary" type="button" id="mrpGenSeed">
              <i class="ri-shuffle-line me-1"></i> Generar
            </button>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Cabello / Top</label>
              <select class="form-select" id="mrpTop"></select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Accesorios</label>
              <select class="form-select" id="mrpAccessories"></select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Ojos</label>
              <select class="form-select" id="mrpEyes"></select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Boca</label>
              <select class="form-select" id="mrpMouth"></select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Ropa</label>
              <select class="form-select" id="mrpClothing"></select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Barba / Bigote</label>
              <select class="form-select" id="mrpFacialHair"></select>
            </div>
          </div>

          <hr>

          <div class="row g-2">
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold">Piel</label>
              <input type="color" class="form-control form-control-color w-100" id="mrpSkinColor" value="#f2d3b1">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold">Cabello</label>
              <input type="color" class="form-control form-control-color w-100" id="mrpHairColor" value="#2f2f2f">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold">Ropa</label>
              <input type="color" class="form-control form-control-color w-100" id="mrpClothingColor" value="#e97e2e">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold">Fondo</label>
              <input type="color" class="form-control form-control-color w-100" id="mrpBgColor" value="#ffffff">
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-outline-secondary" type="button" id="mrpRandom">
              <i class="ri-dice-line me-1"></i> Random
            </button>
            <button class="btn btn-outline-danger" type="button" id="mrpReset">
              <i class="ri-refresh-line me-1"></i> Reset
            </button>
            <button class="btn btn-primary ms-auto" type="button" id="mrpSaveAvatar">
              <i class="ri-save-3-line me-1"></i> Guardar avatar
            </button>
          </div>

          <div class="alert alert-warning mt-3 mb-0">
            <i class="ri-mail-send-line me-1"></i>
            <b>Nota:</b> Al guardar, tu avatar se asociará a tu perfil y podrá utilizarse en asignaciones operativas del MRP.
          </div>

        </div>
      </div>
    </div>

    <!-- Preview -->
    <div class="col-lg-6">
      <div class="card border h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="ri-eye-line me-1"></i> Vista previa</h6>
        </div>
        <div class="card-body d-flex flex-column">

          <div class="p-3 border rounded-3 bg-light d-flex justify-content-center align-items-center flex-grow-1" style="min-height:360px;">
            <div id="mrpAvatarPreview" style="width: 320px; max-width: 100%;"></div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <!-- <button class="btn btn-outline-secondary" type="button" id="mrpCopySvg">
              <i class="ri-file-copy-line me-1"></i> Copiar SVG
            </button> -->
            <button class="btn btn-outline-primary" type="button" id="mrpDlSvg">
              <i class="ri-download-line me-1"></i> SVG
            </button>
            <button class="btn btn-success" type="button" id="mrpDlPng">
              <i class="ri-download-cloud-2-line me-1"></i> PNG
            </button>
          </div>

          <canvas id="mrpCanvas" width="512" height="512" class="d-none"></canvas>

          <hr class="my-3">

          <!-- Upload image -->
          <!-- <h6 class="mb-2"><i class="ri-image-add-line me-1"></i> O subir imagen</h6>
          <input class="form-control" type="file" id="mrpFileAvatar" accept="image/*"> -->
<!-- 
          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-secondary" type="button" id="mrpClearUpload">
              <i class="ri-delete-bin-6-line me-1"></i> Quitar
            </button>
            <button class="btn btn-primary" type="button" id="mrpSaveUpload">
              <i class="ri-save-3-line me-1"></i> Guardar imagen
            </button>
          </div> -->

          <!-- <div class="mt-3 p-3 border rounded-3 bg-light d-flex justify-content-center align-items-center" style="min-height:220px;">
            <img id="mrpUploadPreview" style="max-width: 85%; border-radius: 16px; display:none;" alt="preview">
            <div id="mrpUploadEmpty" class="text-muted text-center">
              <i class="ri-image-line" style="font-size:42px;"></i>
              <div class="mt-2">Selecciona una imagen para previsualizar.</div>
            </div>
          </div> -->

        </div>
      </div>
    </div>

  </div>
</div>




                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end col-->
                    </div>
                    <!--end row-->

                </div>
                <!-- container-fluid -->
            </div><!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        LDR Solutions · MRP
                    </div>
                </div>
            </div>
        </div>
    </footer>
        </div>

<?php footerAdmin($data); ?>

<script>
  window.MRP_AVATAR_SAVED = {
    usuarioid: "<?= (int)$_SESSION['userData']['idusuario'] ?>",
    seed: <?= json_encode($data['usuario']['avatar_seed'] ?? '') ?>,
    gender: <?= json_encode($data['usuario']['avatar_gender'] ?? 'unisex') ?>,
    options: <?= json_encode($data['usuario']['avatar_options'] ?? '') ?>,
    url: <?= json_encode($data['avatarUrl'] ?? '') ?>
  };
</script>


<script type="module">
  // =========================================================
  //  CONFIG (ajusta tu endpoint)
  // =========================================================
  const MRP_ENDPOINT_SAVE_AVATAR = (window.base_url || '') + '/Usuarios/setAvatar';

  import { createAvatar } from "https://esm.sh/@dicebear/core?bundle";
  import { avataaars } from "https://esm.sh/@dicebear/collection?bundle";

  // =========================================================
  //  REFS (ajusta IDs a tus inputs)
  // =========================================================
  const usuarioidEl = document.getElementById('usuarioid');

  const preview = document.getElementById('mrpAvatarPreview');
  const cnv = document.getElementById('mrpCanvas');

  const seed = document.getElementById('mrpSeed');
  const top = document.getElementById('mrpTop');
  const accessories = document.getElementById('mrpAccessories');
  const eyes = document.getElementById('mrpEyes');
  const mouth = document.getElementById('mrpMouth');
  const clothing = document.getElementById('mrpClothing');
  const facialHair = document.getElementById('mrpFacialHair');

  const skinColor = document.getElementById('mrpSkinColor');
  const hairColor = document.getElementById('mrpHairColor');
  const clothingColor = document.getElementById('mrpClothingColor');
  const bgColor = document.getElementById('mrpBgColor');

  const gMale = document.getElementById('mrpMale');
  const gFemale = document.getElementById('mrpFemale');
  const gUnisex = document.getElementById('mrpUnisex');

  const btnGenSeed = document.getElementById('mrpGenSeed');
  const btnRandom = document.getElementById('mrpRandom');
  const btnReset = document.getElementById('mrpReset');
  const btnSaveAvatar = document.getElementById('mrpSaveAvatar');

  const btnCopySvg = document.getElementById('mrpCopySvg');
  const btnDlSvg = document.getElementById('mrpDlSvg');
  const btnDlPng = document.getElementById('mrpDlPng');

  // tu imagen real de perfil en el sidebar/card
  const imgProfile = document.querySelector('.user-profile-image');

  // =========================================================
  //  CATÁLOGOS + PRESETS
  // =========================================================
  const CATALOG = {
    accessories: ["blank","kurt","prescription01","prescription02","round","sunglasses","wayfarers"],
    top: [
      "noHair","hat","turban","winterHat1","winterHat2","winterHat3","winterHat4",
      "longHairBigHair","longHairBob","longHairBun","longHairCurly","longHairCurvy","longHairDreads",
      "longHairFrida","longHairFro","longHairFroBand","longHairNotTooLong","longHairShavedSides",
      "longHairMiaWallace","longHairStraight","longHairStraight2","longHairStraightStrand",
      "shortHairDreads01","shortHairDreads02","shortHairFrizzle","shortHairShaggyMullet",
      "shortHairShortCurly","shortHairShortFlat","shortHairShortRound","shortHairShortWaved",
      "shortHairSides","shortHairTheCaesar","shortHairTheCaesarSidePart"
    ],
    eyes: ["default","close","cry","dizzy","eyeRoll","happy","hearts","side","squint","surprised","wink","winkWacky"],
    mouth: ["default","concerned","disbelief","eating","grimace","sad","screamOpen","serious","smile","tongue","twinkle","vomit"],
    clothing: ["blazerShirt","blazerSweater","collarSweater","graphicShirt","hoodie","overall","shirtCrewNeck","shirtScoopNeck","shirtVNeck"],
    facialHair: ["blank","beardMedium","beardLight","beardMajestic","moustacheFancy","moustacheMagnum"]
  };

  // “Mixto/unisex” sin cara “femenina” por default: hoodie + shortHair
  const PRESET = {
    male: {
      top: "shortHairShortWaved",
      facialHair: "beardLight",
      clothing: "shirtCrewNeck",
      accessories: "blank",
      eyes: "default",
      mouth: "smile",
      hairColor: "#2f2f2f",
      clothingColor: "#0f172a"
    },
    female: {
      top: "longHairStraight",
      facialHair: "blank",
      clothing: "collarSweater",
      accessories: "blank",
      eyes: "happy",
      mouth: "smile",
      hairColor: "#3b2f2f",
      clothingColor: "#e97e2e"
    },
    unisex: {
      top: "shortHairShortCurly",
      facialHair: "blank",
      clothing: "hoodie",
      accessories: "blank",
      eyes: "default",
      mouth: "smile",
      hairColor: "#2f2f2f",
      clothingColor: "#e97e2e"
    }
  };

  function fillSelect(sel, list, placeholder) {
    sel.innerHTML = '';
    const o0 = document.createElement('option');
    o0.value = '';
    o0.textContent = placeholder;
    sel.appendChild(o0);
    list.forEach(v => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      sel.appendChild(opt);
    });
  }

  fillSelect(top, CATALOG.top, '-- Cabello/Top --');
  fillSelect(accessories, CATALOG.accessories, '-- Accesorios --');
  fillSelect(eyes, CATALOG.eyes, '-- Ojos --');
  fillSelect(mouth, CATALOG.mouth, '-- Boca --');
  fillSelect(clothing, CATALOG.clothing, '-- Ropa --');
  fillSelect(facialHair, CATALOG.facialHair, '-- Barba/Bigote --');

  function pick(arr){ return arr[Math.floor(Math.random()*arr.length)]; }
  function normalizeHex(hex){ return String(hex || '').replace('#','').trim(); }

  function currentGender(){
    if (gMale && gMale.checked) return 'male';
    if (gFemale && gFemale.checked) return 'female';
    return 'unisex';
  }
  function setGenderUI(g){
    if (!gMale || !gFemale || !gUnisex) return;
    if (g === 'male') gMale.checked = true;
    else if (g === 'female') gFemale.checked = true;
    else gUnisex.checked = true;
  }

  function applyPreset(type){
    const p = PRESET[type] || PRESET.unisex;
    if (top) top.value = p.top || '';
    if (facialHair) facialHair.value = p.facialHair || '';
    if (clothing) clothing.value = p.clothing || '';
    if (accessories) accessories.value = p.accessories || '';
    if (eyes) eyes.value = p.eyes || '';
    if (mouth) mouth.value = p.mouth || '';
    if (hairColor) hairColor.value = p.hairColor || hairColor.value;
    if (clothingColor) clothingColor.value = p.clothingColor || clothingColor.value;
    render();
  }

  function buildOptions(){
    return {
      seed: (seed?.value || 'usr-193d5885'),
      radius: 18,
      backgroundColor: [ normalizeHex(bgColor?.value || '#ffffff') ],
      skinColor: [ normalizeHex(skinColor?.value || '#f2d3b1') ],
      hairColor: [ normalizeHex(hairColor?.value || '#2f2f2f') ],
      clothingColor: [ normalizeHex(clothingColor?.value || '#e97e2e') ],
      ...(accessories?.value ? { accessories: [accessories.value] } : {}),
      ...(top?.value ? { top: [top.value] } : {}),
      ...(eyes?.value ? { eyes: [eyes.value] } : {}),
      ...(mouth?.value ? { mouth: [mouth.value] } : {}),
      ...(clothing?.value ? { clothing: [clothing.value] } : {}),
      ...(facialHair?.value ? { facialHair: [facialHair.value] } : {})
    };
  }

  function render(){
    const options = buildOptions();
    const avatar = createAvatar(avataaars, options);
    const svg = avatar.toString();

    if (preview) {
      preview.innerHTML = svg;
      const svgEl = preview.querySelector('svg');
      if (svgEl) {
        svgEl.style.width = '320px';
        svgEl.style.maxWidth = '100%';
        svgEl.style.height = 'auto';
      }
    }
    return svg;
  }

  // =========================================================
  //  EXPORT
  // =========================================================
  async function copySvg(){
    const svg = render();
    try {
      await navigator.clipboard.writeText(svg);
      alert('✅ SVG copiado al portapapeles');
    } catch {
      alert('⚠ No se pudo copiar. Revisa permisos del navegador.');
    }
  }

  function downloadSvg(){
    const svg = render();
    const blob = new Blob([svg], { type:'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `avatar_${(seed?.value||'mrp').replace(/\s+/g,'_')}.svg`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  async function downloadPng(){
    // Solo para descarga local (NO guardamos PNG en servidor)
    const svg = render();
    const svgBlob = new Blob([svg], { type:'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    const img = new Image();
    img.crossOrigin = 'anonymous';

    img.onload = () => {
      const ctx = cnv.getContext('2d');
      ctx.clearRect(0,0,cnv.width,cnv.height);
      ctx.fillStyle = bgColor?.value || '#ffffff';
      ctx.fillRect(0,0,cnv.width,cnv.height);

      const scale = Math.min(cnv.width / img.width, cnv.height / img.height);
      const w = img.width * scale;
      const h = img.height * scale;
      const x = (cnv.width - w)/2;
      const y = (cnv.height - h)/2;
      ctx.drawImage(img, x, y, w, h);

      cnv.toBlob((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `avatar_${(seed?.value||'mrp').replace(/\s+/g,'_')}.png`;
        document.body.appendChild(a);
        a.click();
        a.remove();
      }, 'image/png');

      URL.revokeObjectURL(url);
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      alert('⚠ No se pudo convertir a PNG. Descarga SVG o prueba otro navegador.');
    };

    img.src = url;
  }

  // =========================================================
  //  SAVE AVATAR -> BACKEND (UNA SOLA FUNCIÓN)
  // =========================================================
  function svgToDataUrl(svg){
    const encoded = btoa(unescape(encodeURIComponent(svg)));
    return 'data:image/svg+xml;base64,' + encoded;
  }

  async function saveAvatar(){
    const usuarioid = Number(usuarioidEl?.value || 0);
    if (!usuarioid) { alert('⚠ No se recibió usuarioid'); return; }

    const payload = {
      usuarioid,
      gender: currentGender(),
      seed: (seed?.value || 'usr-193d5885'),
      options: buildOptions(),     // JSON completo
      svg: render()                // SVG final
    };

    // UX: actualizar “foto” en pantalla inmediato
    if (imgProfile) imgProfile.src = svgToDataUrl(payload.svg);

    try {
      const res = await fetch(MRP_ENDPOINT_SAVE_AVATAR, {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(()=>null);

      if (!res.ok || !data || data.status === false) {
        console.warn('RESP:', data);
        alert('⚠ No se pudo guardar el avatar. Revisa endpoint/servidor.');
        return;
      }

      // si tu backend regresa url, úsala y evita el dataurl
      if (data.url && imgProfile) imgProfile.src = data.url + '?v=' + Date.now();

      // MUY IMPORTANTE: actualizar cache local “guardado”
      window.MRP_AVATAR_SAVED = {
        usuarioid,
        seed: payload.seed,
        gender: payload.gender,
        options: payload.options,
        url: data.url || (window.MRP_AVATAR_SAVED?.url || '')
      };

      alert('✅ Avatar guardado correctamente');
    } catch (e) {
      console.error(e);
      alert('⚠ Error de red al guardar avatar (endpoint/servidor).');
    }
  }

  // =========================================================
  //  INIT DESDE BD (evita que siempre sea usr-193d5885)
  // =========================================================
  function parseMaybeJson(v){
    if (!v) return null;
    if (typeof v === 'object') return v;
    try { return JSON.parse(v); } catch { return null; }
  }

  function applySavedOptions(savedOptions){
    if (!savedOptions) return;

    const get1 = (x) => Array.isArray(x) ? x[0] : x;

    if (top && get1(savedOptions.top)) top.value = get1(savedOptions.top);
    if (accessories && get1(savedOptions.accessories)) accessories.value = get1(savedOptions.accessories);
    if (eyes && get1(savedOptions.eyes)) eyes.value = get1(savedOptions.eyes);
    if (mouth && get1(savedOptions.mouth)) mouth.value = get1(savedOptions.mouth);
    if (clothing && get1(savedOptions.clothing)) clothing.value = get1(savedOptions.clothing);
    if (facialHair && get1(savedOptions.facialHair)) facialHair.value = get1(savedOptions.facialHair);

    const hexToInput = (arrOrVal, fallback) => {
      const v = get1(arrOrVal);
      if (!v) return fallback;
      const s = String(v).trim();
      return s.startsWith('#') ? s : ('#' + s);
    };

    if (skinColor && savedOptions.skinColor) skinColor.value = hexToInput(savedOptions.skinColor, skinColor.value);
    if (hairColor && savedOptions.hairColor) hairColor.value = hexToInput(savedOptions.hairColor, hairColor.value);
    if (clothingColor && savedOptions.clothingColor) clothingColor.value = hexToInput(savedOptions.clothingColor, clothingColor.value);
    if (bgColor && savedOptions.backgroundColor) bgColor.value = hexToInput(savedOptions.backgroundColor, bgColor.value);
  }

  (function initFromDB(){
    // defaults mínimos
    if (seed && !seed.value) seed.value = 'usr-193d5885';
    if (skinColor && !skinColor.value) skinColor.value = '#f2d3b1';
    if (hairColor && !hairColor.value) hairColor.value = '#2f2f2f';
    if (clothingColor && !clothingColor.value) clothingColor.value = '#e97e2e';
    if (bgColor && !bgColor.value) bgColor.value = '#ffffff';

    const saved = window.MRP_AVATAR_SAVED || null;

    // si no hay nada guardado => unisex preset + render
    if (!saved || (!saved.seed && !saved.options && !saved.url)) {
      setGenderUI('unisex');
      applyPreset('unisex');
      render();
      return;
    }

    // si hay url del avatar, actualiza imagen principal
    if (saved.url && imgProfile) {
      imgProfile.src = saved.url + '?v=' + Date.now();
    }

    // seed guardado
    if (saved.seed && seed) seed.value = saved.seed;

    // gender guardado
    setGenderUI(saved.gender || 'unisex');

    // options guardadas
    const opts = parseMaybeJson(saved.options);
    if (opts) applySavedOptions(opts);
    else applyPreset(saved.gender || 'unisex');

    render();
  })();

  // =========================================================
  //  EVENTS
  // =========================================================
  btnGenSeed?.addEventListener('click', () => {
    seed.value = 'usr-' + Math.random().toString(16).slice(2,10);
    render();
  });

  btnRandom?.addEventListener('click', () => {
    seed.value = 'usr-' + Math.random().toString(16).slice(2,10);

    const gen = currentGender();
    if (gen === 'male') {
      top.value = pick(CATALOG.top.filter(x => x.startsWith('shortHair') || x === 'noHair'));
      facialHair.value = pick(CATALOG.facialHair.filter(x => x !== 'blank'));
    } else if (gen === 'female') {
      top.value = pick(CATALOG.top.filter(x => x.startsWith('longHair')));
      facialHair.value = 'blank';
    } else {
      top.value = pick(CATALOG.top);
      facialHair.value = pick(CATALOG.facialHair);
    }

    accessories.value = pick(CATALOG.accessories);
    eyes.value = pick(CATALOG.eyes);
    mouth.value = pick(CATALOG.mouth);
    clothing.value = pick(CATALOG.clothing);
    clothingColor.value = pick(['#e97e2e','#0f172a','#2563eb','#16a34a','#ef4444']);
    render();
  });

  btnReset?.addEventListener('click', () => {
    seed.value = 'usr-193d5885';
    skinColor.value = '#f2d3b1';
    hairColor.value = '#2f2f2f';
    clothingColor.value = '#e97e2e';
    bgColor.value = '#ffffff';
    setGenderUI('unisex');
    applyPreset('unisex');
  });

  [seed, top, accessories, eyes, mouth, clothing, facialHair, skinColor, hairColor, clothingColor, bgColor].forEach(el => {
    el?.addEventListener('input', render);
    el?.addEventListener('change', render);
  });

  gMale?.addEventListener('change', () => applyPreset('male'));
  gFemale?.addEventListener('change', () => applyPreset('female'));
  gUnisex?.addEventListener('change', () => applyPreset('unisex'));

  btnCopySvg?.addEventListener('click', copySvg);
  btnDlSvg?.addEventListener('click', downloadSvg);
  btnDlPng?.addEventListener('click', downloadPng);

  btnSaveAvatar?.addEventListener('click', saveAvatar);

  // Render inicial (por si acaso)
  render();
</script>
