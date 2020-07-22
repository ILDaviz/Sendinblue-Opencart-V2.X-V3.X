<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content">
   <div class="page-header">
      <div class="container-fluid">
         <div class="pull-right">
            <button type="submit" form="form-account" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
            <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
         </div>
         <h1>
            <?php echo $heading_title; ?>
         </h1>
         <ul class="breadcrumb">
            <?php foreach ($breadcrumbs as $breadcrumb) { ?>
            <li>
               <a href="<?php echo $breadcrumb['href']; ?>">
               <?php echo $breadcrumb['text']; ?>
               </a>
            </li>
            <?php } ?>
         </ul>
      </div>
   </div>
   <style>
      h3.sibtitle {
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
      }
      p {
      line-height:20px;
      }
      li {
      line-height:22px;
      }
      h5 {
      margin-top: 9px;
      margin-bottom: 9px;
      font-weight:bold;
      }
      .sibocs {
      margin-bottom:20px;
      }
      input.sib-radio {
      float: left;
      margin-right: 5px;
      top: -2px;
      }
      input.ocs-check {
      float: left;
      margin-right: 5px;
      margin-bottom:10px;
      }
      button.ocs-tooltip {
      font-size: 10px;
      background: #1e91cf;
      border: 1px solid #197bb0;
      border-radius: 2px;
      color: #fff;
      height: 20px;
      width:20px;
      margin-left:5px;
      }
      ul.logged {
      padding-top: 5px;
      line-height: 1.7;
      padding-left: 0px;
      }
      span.help {
    padding-top: 5px;
    font-style: italic;
    float: left;
}
.row.row-eq-height {
border-top: 1px dashed #ddd;
    margin-top: 5px;
    padding-top: 5px;
}
.row.row-eq-height .col-xs-6.col-md-4 {
    padding-top: 10px;
}
      i:before {
    font: normal normal normal 14px/1 FontAwesome !important;
}
.fa {
    font-family: 'Open Sans', sans-serif;
}
   </style>
   <div class="container-fluid">
      <?php if ($error_warning) { ?>
      <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
         <?php echo $error_warning; ?>
         <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php } ?>
      <?php if (isset($success)) { ?>
      <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i>
         <?php echo $success; ?>
         <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php } ?>
      <div class="panel panel-default">
         <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i>
               <?php echo $text_edit; ?>
            </h3>
         </div>
         <div class="panel-body">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-account" class="form-horizontal">
               <ul class="nav nav-tabs">
                  <li class="active"><a href="#tab-about" data-toggle="tab">About SendinBlue</a></li>
                  <li><a href="#tab-contacts" data-toggle="tab">Contacts Manager</a></li>
                  <li><a href="#tab-automation" data-toggle="tab">Automation</a></li>
                  <li><a href="#tab-emails" data-toggle="tab">Transactional Emails</a></li>
                  <li><a href="#tab-support" data-toggle="tab">Support</a></li>
               </ul>
               <div class="tab-content">
                  <div class="tab-pane active" id="tab-about">
                     <div class="container-fluid">
                        <div class="form-group">
                           <div class="col-sm-12">
                              <img src="../admin/view/image/sendinbluelogo.png" atl="" title="" class="sibocs" />
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3>Activate your plugin</h3>
                                    <hr>
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3"><strong>Enter your API v3 Key:</strong><button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="Please enter your API key from your SendinBlue account and if you don't have it yet, please go to www.sendinblue.com and subscribe. You can then get the API key from https://my.sendinblue.com/integration">
                                       ? </button>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"><input type="text" class="form-control" name="api_key" value="<?php echo $api_key; ?>" />
                                    </div>
									<?php foreach ($hidden as $k => $v) { ?>
									<input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>" />
									<?php } ?>
									
								</div>
                              </div>
								<hr>
								<div class="row">
									<div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
										<div class="col-xs-12 col-sm-6 col-md-6 col-lg-3">
											<strong>You're currently logged in as:</strong>
										</div>
										<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
										    <ul class="logged">
											<?php if ($full_account) { ?>
											<?php foreach ($full_account as $k => $v) { ?>
												<?php if (is_array($v)) { ?>
													<li><?php echo (ucwords(str_replace('_', ' ', $k)) .': '); ?></li>
													<ul>
													<?php foreach ($v as $k2 => $v2) { ?>
														<?php if (is_array($v2)) { ?>
															<li><?php echo (ucwords(str_replace('_', ' ', $k2)) .': '); ?></li>
															<ul>
															<?php foreach ($v2 as $k3 => $v3) { ?>
																<li><?php echo (ucwords(str_replace('_', ' ', $k3)) .': ' . $v3); ?></li>
															<?php } ?>
															</ul>
														<?php } else { ?>
															<li><?php echo (ucwords(str_replace('_', ' ', $k2)) .': ' . $v2); ?></li>
														<?php } ?>
													<?php } ?>
													</ul>
												<?php } else { ?>
													<li><?php echo (ucwords(str_replace('_', ' ', $k)) .': ' . $v); ?></li>
												<?php } ?>
											<?php } ?>
											<?php } elseif ($account_details) { ?>							  
											<?php foreach ($account_details as $k => $v) { ?>
												<?php echo $k . ': ' . $v . "<br>"; ?>
											<?php } ?>
											<?php } elseif ($account) { ?>
											<?php echo $account; ?>
											<?php } ?>
											</ul>
                                    </div>
									</div>
								</div>
								
                              <hr>
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3>Getting Started</h3>
                                    <ul>
                                       <li>1. Create your free Sendinblue account: <a href="https://www.sendinblue.com/users/signup/" target="_blank">SendinBlue.com</a><br><a href="https://www.sendinblue.com/users/signup/" form="form-account" data-toggle="tooltip" title="Create an Account" class="btn btn-primary" style="margin:5px 0px;"><i class="fa fa-check">Create an Account</i></a></li>
                                       <li>2. Activate the SendinBlue plugin here above.</strong>
                                       </li>
                                    </ul>
                                 </div>
                              </div>
                              <hr>
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-9">
                                    <h5>With SendinBlue, you can build and grow relationships with your contacts and customers.</h5>
                                    <ul>
                                       <li>Automatically sync your OpenCart opt-in contacts with your SendinBlue Account</li>
                                       <li>Easily create engaging, mobile-friendly emails</li>
                                       <li>Schedule email and text message campaigns</li>
                                       <li>Manage transactional emails with better deliverability, custom templates, and real-time analytics</li>
                                    </ul>
                                 </div>
                              </div>
                              <hr>
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h5 class="sibtitle">Why use SendinBlue?</h5>
                                    <ul>
                                       <li>Reach the inbox with optimized deliverability</li>
                                       <li>Unbeatable pricing - the best value in email marketing</li>
                                       <li>Friendly customer support by phone and email</li>
                                    </ul>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="tab-pane" id="tab-contacts">
                     <div class="container-fluid">
                        <div class="form-group">
                           <div class="col-sm-12">
                              <img src="../admin/view/image/sendinbluelogo.png" atl="" title="" class="sibocs" />
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3 class="sibtitle">Activate SendinBlue to manage contacts</h3>
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                       <p>Use SendinBlue to manage your contacts?</p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-7">
                                       <input type="radio" name="contactmanager" value="1" data-toggle="collapse" data-target=".collapseContacts:not(.in)" <?php echo $contactmanager ? 'checked="checked"' : ''; ?>>Yes
                                       <input type="radio" name="contactmanager" value="0" data-toggle="collapse" data-target=".collapseContacts.in" <?php echo !$contactmanager ? 'checked="checked"' : ''; ?>>No
                                       <!--a href="#" form="form-account" data-toggle="tooltip" title="Update" class="btn btn-primary"><i class="fa fa-update">Update</i></a-->
                                       <button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="If you activate this feature, your new contacts will be automatically added to SendinBlue or unsubscribed from SendinBlue. To synchronize the other way around (SendinBlue to OpenCart), you should run the url (mentioned below) each day.">
                                       ?
                                       </button>
                                       <br>
                                       <span class="help">Select "Yes" to automatically add your new contacts to your SendinBlue account.<br>For each contact, order data will be sychronized as transactional attributes: ORDER_ID, ORDER_DATE, ORDER_PRICE.</span>
                                    </div>
                                 </div>
                              </div>
                              <hr>
                              <br>
							  <div class="collapseContacts panel-collapse collapse <?php echo ($contactmanager) ? 'in' : ''; ?>">
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                       <p style="text-align:right"><strong>Your Lists:</strong><button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="Select the contact list where you want to save the contacts of your OpenCart store. By default, we have created a list 'OpenCart' in your SendinBlue account and selected it">
                                          ?
                                          </button>
                                       </p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-9">
                                       <div class="well well-sm" style="height: 150px; overflow: auto;">
                                          <?php foreach ($lists as $list) { ?>
                                          <div class="checkbox">
                                             <label>
                                             <?php if (in_array($list['id'], $active_lists)) { ?>
                                             <input type="checkbox" name="active_lists[]" value="<?php echo $list['id']; ?>" checked="checked" />
                                             <?php echo $list['name']; ?> (subs: <?php echo $list['totalSubscribers']; ?>)
                                             <?php } else { ?>
                                             <input type="checkbox" name="active_lists[]" value="<?php echo $list['id']; ?>" />
                                             <?php echo $list['name']; ?> (subs: <?php echo $list['totalSubscribers']; ?>)
                                             <?php } ?>
                                             </label>
                                          </div>
                                          <?php } ?>
                                       </div>
                                       
						
									</div>
                                </div>
                            </div>
							<hr>
                              <br>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                       <p>Enable Attribute Mapping?</p>
                                       
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                       <input type="radio" name="attribute_mapping" value="1" data-toggle="collapse" data-target=".collapseAttributes:not(.in)" <?php echo $attribute_mapping ? 'checked="checked"' : ''; ?>>Yes
                                       <input type="radio" name="attribute_mapping" value="0" data-toggle="collapse" data-target=".collapseAttributes.in" <?php echo !$attribute_mapping ? 'checked="checked"' : ''; ?>>No
                                       <!--a href="#" form="form-account" data-toggle="tooltip" title="Update" class="btn btn-primary"><i class="fa fa-update">Update</i></a-->
                                       <button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="Map fields from opencart to the desired equivalent in your SendinBlue account.">
                                       ?
                                       </button><br>
                                       <span class="help">If you select "Yes", you'll be able to match your OpenCart attributes with your SendinBlue attributes.</span>
                                    </div>
                                 </div>
                              </div>
                              <br><br>
							  <div class="collapseAttributes panel-collapse collapse <?php echo ($attribute_mapping) ? 'in' : ''; ?>">
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                       <p style="text-align:right"><strong>Attribute Mapping:</strong></p>
                                    </div>
                                    <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                                        <div class="row">
                                          <div class="col-xs-6 col-md-4">
                                             <h4>OpenCart Attributes</h4>
                                          </div>
                                          <div class="col-xs-6 col-md-6">
                                            <h4>SendinBlue Attributes</h4><span class="help"><a href="https://my.sendinblue.com/lists/add-attributes" target="_blank" style="margin-top: 0px; padding-bottom: 10px; float:left; padding-top: 0px;">Click here to create attributes on your Sendinblue account.</a></span>
                                          </div>
                                       </div>
                                       <?php foreach ($cart_attributes as $i => $cart_attribute) {?>
                                       <div class="row row-eq-height">
                                          <div class="col-xs-6 col-md-4">
                                             <?php echo $cart_attribute; ?>
                                          </div>
                                          <div class="col-xs-6 col-md-6">
											 <input type="hidden" name="cart_attribute_map[<?php echo $i; ?>][field]" value="<?php echo $cart_attribute; ?>" />
                                             <select name="cart_attribute_map[<?php echo $i; ?>][sib]" class="form-control">
                                                <option value="">Disabled</option>
                                                <?php foreach ($attributes as $attribute) { ?>
                                                <option value="<?php echo $attribute['name']; ?>" <?php echo isset($cart_attribute_map[$i]['sib']) && $cart_attribute_map[$i]['sib'] == $attribute['name'] ? 'selected="selected"' : ''; ?>><?php echo $attribute['name']; ?></option>
                                                <?php } ?>
                                             </select>
                                          </div>
                                       </div>
                                       <?php } ?>
									 
                                    </div>
									
                                 </div>
                              </div>
							  </div>
							<hr>
							  <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                       <p>Historical Data</p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-7">
                                       <span class="help"><strong>NOTE:</strong> Before you import, be sure to have selected a list and to have all your attributes mapped correctly.</span><br><br>
									   <a id="importcustomers" form="form-account" data-toggle="tooltip" title="Import Data" class="btn btn-primary"><i class="fa fa-update">Sync Existing Customers</i></a><br/><br/>
                                       <a id="importorders" form="form-account" data-toggle="tooltip" title="Import Data" class="btn btn-primary"><i class="fa fa-update">Sync Existing Orders</i></a><br/><br/>
									   <a id="synccontacts" form="form-account" data-toggle="tooltip" title="Sync Contacts" class="btn btn-primary"><i class="fa fa-update">Sync Contacts From SendInBlue</i></a>
									   <br><span class="help">Run <a href="http://sib.vazcreations.com/index.php?route=extension/module/sendinblue/syncContacts">this url</a> daily with a cron job to sync contacts from SendinBlue to OpenCart.</span>
                                       <br>
                                    </div>
                                 </div>
                              </div>
									   
					          </div>                    
                           </div>
                        </div>
                     </div>
                  </div>
              
                  <div class="tab-pane" id="tab-emails">
                     <div class="container-fluid">
                        <div class="form-group">
                           <div class="col-sm-12">
                              <img src="../admin/view/image/sendinbluelogo.png" atl="" title="" class="sibocs" />
                              <br>
                              <?php if ($hasSMTP) { ?>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3 class="sibtitle">Manage Transactional Emails</h3>
                                    <div class="col-xs-12 col-sm-6 col-md-8 col-lg-4">
                                       <p style="text-align:right"><strong>Manage Transactional Emails</strong></p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-8">
                                       <input type="radio" name="transactionalemails" data-toggle="collapse" data-target=".collapseEmails:not(.in)" value="1" <?php echo $transactionalemails ? 'checked="checked"' : ''; ?>> Yes 
                                       <input type="radio" name="transactionalemails" data-toggle="collapse" data-target=".collapseEmails.in" value="0" <?php echo !$transactionalemails ? 'checked="checked"' : ''; ?>> No 
                                       <!--&nbsp; <a href="#" form="form-account" data-toggle="tooltip" title="Update" class="btn btn-primary"><i class="fa fa-update">Update</i></a-->
                                       <br><br>
                                    </div>
                                 </div>
                              </div>
                              <br>
							 <div class="collapseEmails panel-collapse collapse <?php echo ($transactionalemails) ? 'in' : ''; ?>">
							  <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-6 col-md-4">
                                       <p style="text-align:right"><strong>Enter your SMTP Password from your SendinBlue Account Page and Click Save before continuing.</strong><br/><a href="https://account.sendinblue.com/advanced/api" target="_blank">SendinBlue Account Page</a></p>
                                    </div>
                                    <div class="col-xs-6 col-md-4">
                                       <input type="password" id="smtp_password" name="smtp_password" class="form-control" style="margin-bottom:5px" value="<?php echo $smtp_password; ?>"><br><br>
                                    </div>
                                 </div>
                              </div>
							    <hr>
							  <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-6 col-md-4">
                                       <p style="text-align:right"><strong>Send email test From/To:</strong></p>
                                    </div>
                                    <div class="col-xs-6 col-md-4">
                                       <input type="text" id="testemail" name="testemail" placeholder="abc@xyz.com" class="form-control" style="margin-bottom:5px" value="<?php echo $testemail; ?>"><a id="sendtestemail" form="form-account" data-toggle="tooltip" title="Update" class="btn btn-primary"><i class="fa fa-update">Send</i></a><br><br>
                                    </div>
                                 </div>
                              </div>
                            
							  <hr>
							<div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                                       <p style="text-align:right"><strong>Email Confirmation:</strong></p>
                                    </div>
									<div class="col-xs-12 col-sm-6 col-md-4 col-lg-8">
                                       <input type="radio" name="confirmation" checked="checked" value="0" class="sib-radio" data-toggle="collapse" data-target=".collapseOne.in" <?php echo !$confirmation ? 'checked="checked"' : ''; ?>>
                                       <h5>No confirmation</h5>
                                       <span class="help">With this option, contacts are directly added to your list when they enter their email address. No confirmation email is sent.</span>
                                       <br>
                                       <hr>
                                       <input type="radio" name="confirmation" value="1" class="sib-radio" data-toggle="collapse" data-target=".collapseOne.in" <?php echo $confirmation == 1 ? 'checked="checked"' : ''; ?>>
                                       <h5>Simple confirmation<button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="This confirmation email is one of your SMTP templates. By default, we have created a Default Template - Simple Confirmation.">
                                          ?
                                          </button>
                                       </h5>
                                       <select name="simple_confirmation_template" class="form-control">
                                          <?php foreach ($templates as $template) { ?>	
                                          <option value="<?php echo $template['id']; ?>" <?php echo $simple_confirmation_template == $template['id'] ? 'selected="selected"' : ''; ?>><?php echo $template['name']; ?></option>
                                          <?php } ?>
                                       </select>
                                       <hr>
                                       <input type="radio" name="confirmation" value="2" class="sib-radio" data-toggle="collapse" data-target=".collapseOne:not(.in)" <?php echo $confirmation == 2 ? 'checked="checked"' : ''; ?>>
                                       <h5><strong>Double opt-in confirmation</strong><button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="If you select the Double Opt-in confirmation, subscribers will receive an email inviting them to confirm their subscription. Before confirmation, the contact will be saved in the FORM folder, on the Temp - DOUBLE OPT-IN list. After confirmation, the contact will be saved in the Corresponding List selected below.">
                                          ?
                                          </button>
                                       </h5>
                                       <div class="collapseOne panel-collapse collapse <?php echo ($confirmation == 2) ? 'in' : ''; ?>">
                                          <select name="doubleoptintemplate" class="form-control">
                                             <?php foreach ($doubleopt_templates as $template) { ?>	
                                             <option value="<?php echo $template['id']; ?>" <?php echo $doubleoptintemplate == $template['id'] ? 'selected="selected"' : ''; ?>><?php echo $template['name']; ?></option>
                                             <?php } ?>
                                          </select>
                                          <hr>
                                          <input type="checkbox" name="useredirect" <?php echo $useredirect ? 'checked="checked"' : ''; ?> class="sib-radio">
                                          <h5><strong>Redirect URL after clicking in the validation email</strong></h5>
                                          <span class="help">Redirect your contacts to a landingpage or your website once they have clicked on the confirmation link in the email.</span><br><br>
                                          <input name="redirecturl" class="form-control" value="<?php echo $redirecturl; ?>">
                                          <hr>
                                          <input name="usefinaltemplate" type="checkbox" name="sendconfirm" <?php echo $usefinaltemplate ? 'checked="checked"' : ''; ?> class="sib-radio">
                                          <h5><strong>Send a final confirmation email</strong></h5>
                                          <span class="help">Once a contact has clicked in the double opt-in confirmation email, send them a final confirmation email.</span>
                                          <br><br>
                                          <select name="finaltemplate" class="form-control">
                                             <?php foreach ($templates as $template) { ?>	
                                             <option value="<?php echo $template['id']; ?>" <?php echo $finaltemplate == $template['id'] ? 'selected="selected"' : ''; ?>><?php echo $template['name']; ?></option>
                                             <?php } ?>
                                          </select>
                                          <!--a href="#" form="form-account" data-toggle="tooltip" title="Update" class="btn btn-primary"><i class="fa fa-check">Update</i></a--><br><br>
                                          <!--span class="help">To syncronize the emails of your customers from SendinBlue platform to your ecommerce website, you should rund this link each day.</span-->
                                       </div>
                                    </div>
								</div>
                            </div>
							  
							  
							  
                              <hr>
							  
                              
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                                       <p style="text-align:right"><strong>Notification Activation:</strong></p>
                                    </div>
                                    <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
										<p class="help">Here you can override an OpenCart notification email with a SendinBlue Template instead</p>
										<div class="row">
                                          <div class="col-xs-6 col-md-4">
                                             <h4>OpenCart Status</h4>
                                          </div>
                                          <div class="col-xs-6 col-md-6">
                                            <h4>SendinBlue Template</h4>
                                          </div>
                                       </div>
                                       <?php foreach ($order_statuses as $i => $order_status) {?>
                                       <div class="row row-eq-height">
                                          <div class="col-xs-6 col-md-4">
                                             <?php echo $order_status['name']; ?>
                                          </div>
                                          <div class="col-xs-6 col-md-6">
											<input type="hidden" name="order_status_email[<?php echo $i; ?>][order_status_id]" value="<?php echo $order_status['order_status_id']; ?>" />
                                             <select name="order_status_email[<?php echo $i; ?>][template_id]" class="form-control">
                                                <option value="0">Disabled</option>
                                                <?php foreach ($templates as $template) { ?>
                                                <option value="<?php echo $template['id']; ?>" <?php echo isset($order_status_email[$i]['template_id']) && $order_status_email[$i]['template_id'] == $template['id'] ? 'selected="selected"' : ''; ?>><?php echo $template['name']; ?></option>
                                                <?php } ?>
                                             </select>
                                          </div>
                                       </div>
                                       <?php } ?>
                                    </div>
                                 </div>
                              </div>
                              <br>
                              <!--div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3 class="sibtitle">Manage Text Messages</h3>
                                    <div class="row">
                                       <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                          <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                             <strong>Currently you have <span style="color:blue;"><?php echo $sms_left; ?></span> SMS credits left. To buy more credits, please click <a href="https://www.sendinblue.com/pricing?utm_source=opencart_plugin&utm_medium=plugin&utm_campaign=module_link/#sms" target="_blank">here.</a></strong>
                                          </div>
                                          <div class="col-xs-12 col-sm-6 col-md-6 col-lg-8">
                                             &nbsp; 
                                          </div>
                                          <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                             <div class="col-xs-12 col-sm-6 col-md-8 col-lg-4">
                                                <p style="text-align:right"><strong>Do you want to be notified by email when you do not have enough credits?</strong></p>
                                             </div>
                                             <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                                <input type="radio" name="smsnotify" value="1" <?php echo $smsnotify ? 'checked="checked"' : ''; ?>> Yes 
                                                <input type="radio" name="smsnotify" value="0" <?php echo !$smsnotify ? 'checked="checked"' : ''; ?>> No<br><br>
                                                <strong>Email:</strong>  <br>
                                                <input type="text" name="alertemail" placeholder="example@domain.com" value="<?php echo $alertemail; ?>" class="form-control" style="margin-bottom:5px">
                                                <strong>Threshold:</strong>  <br>
                                                <input type="text" name="threshold" placeholder="11" value="<?php echo $threshold; ?>" class="form-control" style="margin-bottom:5px">
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div-->
                              <br>
                              <!--div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3 class="sibtitle">Manage transactional SMS</h3>
                                    <div class="col-xs-12 col-sm-6 col-md-8 col-lg-4">
                                       <p style="text-align:right"><strong>Send transactional SMS for all country:</strong></p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                       <input type="radio" name="usesmscountry" value="1" <?php echo $usesmscountry ? 'checked="checked"' : ''; ?>> Yes 
                                       <input type="radio" name="usesmscountry" value="0" <?php echo !$usesmscountry ? 'checked="checked"' : ''; ?>> No &nbsp; 
                                       <br>
                                       <br>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 col-lg-4">
                                       <p style="text-align:right"><strong>Select country for SMS service:</strong></p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                       <select name="smscountry" class="form-control">
										<?php foreach ($countries as $country) { ?>
                                          <option value="<?php echo $country['iso_code_2']; ?>"><?php echo $country['name']; ?></option>
										<?php } ?>
                                       </select>
                                       <br>
                                       <br>
                                    </div>
                                 </div>
                              </div-->
                              <br>                             
							  </div>
							  <?php } else { ?>
                              <p>Your SMTP platform is disabled, please ask for its activation by sending an email to <a href="mailto:contact@sendinblue.com">contact@sendinblue.com</a></p>
                              <?php } ?>
                           </div>
                        </div>
                     </div>
                  </div>
				  
				  <div class="tab-pane" id="tab-automation">
                     <div class="container-fluid">
                        <div class="form-group">
                           <div class="col-sm-12">
                              <img src="../admin/view/image/sendinbluelogo.png" atl="" title="" class="sibocs" />
                              <br>
                              <?php if ($tracking_id) { ?>
                              <div class="row">
                                 <div class="col-xs-12 col-sm-6 col-md-8 col-lg-12">
                                    <h3 class="sibtitle">Automation</h3>
                                    <div class="col-xs-12 col-sm-6 col-md-8 col-lg-4">
                                       <p style="text-align:right">Activate marketing automation through Sendinblue </p>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                       <input type="hidden" name="tracking_id" value="<?php echo $tracking_id; ?>" />
                                       <input type="radio" name="automation" value="1" <?php echo $automation ? 'checked="checked"' : ''; ?>> Yes 
                                       <input type="radio" name="automation" value="0" <?php echo !$automation ? 'checked="checked"' : ''; ?>> No &nbsp; <button type="button" class="ocs-tooltip" data-toggle="tooltip" data-placement="right" title="Choose Yes if you want to use SendinBlue Automation to track your website activity">
                                       ?
                                       </button><br><br>
                                    </div>
                                 </div>
                              </div>
                              <?php } else { ?>
                              <p>Your Marketing Automation platform is disabled, to activate it, go to <a href="https://automation.sendinblue.com/try" target="_blank">this</a> page and click on the TRY AUTOMATION FOR FREE button.</p>
                              <?php } ?>	
                           </div>
                        </div>
                     </div>
                  </div>
				  
                  <div class="tab-pane" id="tab-support">
                     <div class="container-fluid">
                        <div class="form-group">
                           <div class="col-sm-12">
                              <img src="../admin/view/image/sendinbluelogo.png" atl="" title="" class="sibocs" />
                              <br>
                              <p><b>Module version:</b> v1.304</p>
                              <hr>
                              <h5><strong>Contact the SendinBlue Team</strong></h5>
                              <p><b>Email:</b> contact@sendinblue.com</p>
                              <p>To learn more, visit: <a href="https://sendinblue.com/">sendinblue.com</a></p>
                              <hr>
                              <h5><strong>Contact the Developers Team</strong></h5>
                              <p><b>Email:</b> info@opencartspecialist.com</p>
                              <p><b>Website:</b> <a href="https://opencartspecialist.com/">opencartspecialist.com</a></p>
                              <p><b>Module page:</b> <a href="#">OpenCart Module Page</a></p> 
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>

<script type="text/javascript">
$('#sib_lists').change(function() {
	
});

$('#sendtestemail').click(function() {
	
	var $this = $(this);
	var testemail = ($('input[id=\'testemail\']'));
	
	$.ajax({
		url: 'index.php?route=extension/module/sendinblue/ajax_send_test_mail&<?php echo $token_key; ?>=<?php echo $token; ?>',
		type: 'post',
		data: 'testemail='+testemail.val(),
		dataType: 'json',
		beforeSend: function() {
			$this.button('loading');
			$('.alert').remove();
		},
		success: function(json) {
			$('.alert').remove();

			if (json['success']) {
				testemail.after('<div class="alert alert-success"><i class="fa fa-check"></i> '+json['success']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			} else {
				testemail.after('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+json['error']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        },
		complete: function() {
			$this.button('reset');
		}
	});
});

$('#importcustomers').click(function() {
	
	var $this = $(this);
	
	$.ajax({
		url: 'index.php?route=extension/module/sendinblue/ajax_import_customers&<?php echo $token_key; ?>=<?php echo $token; ?>',
		type: 'post',
		data: $('input[name^="active_lists"]:checked, :input[name^="cart_attribute_map"]').serialize(),
		dataType: 'json',
		beforeSend: function() {
			$this.button('loading');
			$('.alert').remove();
		},
		success: function(json) {
			$('.alert').remove();

			if (json['success']) {
				$this.after('<div class="alert alert-success"><i class="fa fa-spinner fa-spin"></i> '+json['success']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$.ajax({
					url: 'index.php?route=extension/module/sendinblue/ajax_getprocess&<?php echo $token_key; ?>=<?php echo $token; ?>&pid='+json['pid'],
					dataType: 'json',
					success: function(json) {
						$('.alert').remove();
						if (json['success']) {
							//alert('Process ' + json['pid'] + ' completed');
							$this.after('<div class="alert alert-success"><i class="fa fa-check"></i> Process '+json['pid']+' Completed!<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
						} else {
							//alert('Process ' + json['pid'] + ' taking longer than expected...');
							$this.after('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Process '+json['pid']+' taking longer than expected...<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});
				
			} else {
				$this.after('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> '+json['error']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        },
		complete: function() {
			$this.button('reset');
		}
	});
});

$('#importorders').click(function() {
	
	var $this = $(this);
	
	$.ajax({
		url: 'index.php?route=extension/module/sendinblue/ajax_import_orders&<?php echo $token_key; ?>=<?php echo $token; ?>',
		type: 'post',
		data: $('input[name^="active_lists"]:checked, :input[name^="cart_attribute_map"]').serialize(),
		dataType: 'json',
		beforeSend: function() {
			$this.button('loading');
			$('.alert').remove();
		},
		success: function(json) {
			$('.alert').remove();

			if (json['success']) {
				$this.after('<div class="alert alert-success"><i class="fa fa-spinner fa-spin"></i> '+json['success']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				
				$.ajax({
					url: 'index.php?route=extension/module/sendinblue/ajax_getprocess&<?php echo $token_key; ?>=<?php echo $token; ?>&pid='+json['pid'],
					dataType: 'json',
					success: function(json) {
						$('.alert').remove();
						if (json['success']) {
							//alert('Process ' + json['pid'] + ' completed');
							$this.after('<div class="alert alert-success"><i class="fa fa-check"></i> Process '+json['pid']+' Completed!<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
						} else {
							//alert('Process ' + json['pid'] + ' taking longer than expected...');
							$this.after('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Process '+json['pid']+' taking longer than expected...<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});
				
			} else {
				$this.after('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> '+json['error']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        },
		complete: function() {
			$this.button('reset');
		}
	});
});

$('#synccontacts').click(function() {
	
	var $this = $(this);
	
	$.ajax({
		url: 'index.php?route=extension/module/sendinblue/ajax_sync_contacts&<?php echo $token_key; ?>=<?php echo $token; ?>',
		type: 'get',
		dataType: 'json',
		beforeSend: function() {
			$this.button('loading');
			$('.alert').remove();
		},
		success: function(json) {
			$('.alert').remove();

			if (json['success']) {
				$this.after('<div class="alert alert-success"><i class="fa fa-check"></i>'+json['success']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			} else {
				$this.after('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> '+json['error']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        },
		complete: function() {
			$this.button('reset');
		}
	});
});

// Ajax Loading animation
$(document).ready(function() {
	$('body').prepend('<div id="body_loading" style="display: none;"><i class="fa fa-spinner">Please Wait...</i></div>');
	$('#body_loading').css({
		/*"background": "url('catalog/view/javascript/jquery/owl-carousel/AjaxLoader.gif') no-repeat scroll center center #CCCCCC",*/
		"background-color": "#CCCCCC",
		"border": "1px solid #666666",
		"font": "16px Tahoma,Geneva,sans-serif",
		"height": "80px",
		"left": "45%",
		"margin-left": "-50px",
		"margin-top": "-50px",
		"overflow": "auto",
		"padding": "25px",
		"position": "fixed",
		"text-align": "center",
		"vertical-align": "middle",
		"top": "50%",
		"width": "230px",
		"z-index": "2"
	});

	var submitted = false;
	$(window).bind('beforeunload', function() {
		$('div#body_loading').show();
	});
	$('body form').submit(function() {
		$('div#body_loading').show();
	});
	$('body').ajaxStart(function() {
		$('div#body_loading').show();
	});
	$('body').ajaxStop(function() {
		$('div#body_loading').hide();
	});
});


// Ajax Save
$('.panel-default').on('change', ':input', function(event) {
	if (event.target.name == 'api_key') {
		ajaxsave(event);
	}
});

if (!window.console) {var console = {};}
if (!console.log) {console.log = function() {};}

function ajaxsave(event) {
	//alert(event.target.name);

	var fieldName = event.target.name;
	if (fieldName == '') { return false; }
	var fieldValue = event.target.value;
	var doDelete = 0;

	// Get only the input name without array data
	fieldOnly = fieldName.match(/^([^\[]+)/, fieldName);
	//alert(fieldOnly[0]);

	var $this = $(this);

	if (event.target.type == 'checkbox') {
		if ($(':input[name^=' + fieldOnly[0] + ']:checked').length > 0) {
			var postVar = $(':input[name^=' + fieldOnly[0] + ']:checked');
		} else {
			doDelete = 1;
			var postVar = $(':input[name^=' + fieldOnly[0] + ']');
		}
	} else if (event.target.type == 'radio') {
		var postVar = $(':input[name^=' + fieldOnly[0] + ']:checked');
	} else {
		var postVar = $(':input[name^=' + fieldOnly[0] + ']');
	}

	$.ajax({
		type: 'POST',
		url: 'index.php?route=extension/module/sendinblue/ajaxsave&token=<?php echo $token; ?>&user_token=<?php echo $token; ?>&name=' + fieldName + '&value=' + encodeURIComponent(fieldValue) + '&delete=' + doDelete,
		dataType: 'json',
		data: postVar,
		beforeSend: function() {
			$('.ajax_success').remove();
			$('.ajax_warning').remove();
			//$(event.target).after('<span class="wait">&nbsp;<i class="fa fa-spinner"></i></span>');
		},
		success: function(json) {
			if (json['success']) {
				$(event.target).after('<i class="ajax_success fa fa-check">&nbsp;</i>');
				if (fieldName == 'api_key') {
					location.reload(true);
				} else {
					console.log(json['success']);
				}
			} else {
				$(event.target).after('<i class="ajax_warning fa fa-exclamation-triangle">&nbsp;</i>');
				console.log(json['success']);
			}
		},
		complete: function() {
			$('.wait').remove();
			$('.ajax_success').fadeOut(3000, function(){$(this).remove()});
			$('.ajax_warning').fadeOut(3000, function(){$(this).remove()});
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

</script>


<?php echo $footer; ?>