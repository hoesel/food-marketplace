<?php

class VerkaufenController extends Zend_Controller_Action
{
    public function indexAction(){
    }

    public function registerAction(){
        $request = $this->getRequest();
        $form = new Form_RegisterSeller();

        if($request->getPost('Anmelden')){
            if($form->isValid($request->getPost())){
                $exists = Model_User::findByEmail($request->getParam('email', true));
                if($exists){
                    $this->view->registerError = 'Die angegebenen E-Mail-Adresse ist bereits vorhanden.';
                }
                else{
                    if($request->getPost('password1') != $request->getPost('password2')){
                        $this->view->registerError = 'Die angegebenen Passwörter stimmen nicht überein.';
                    }
                    else{
                        $user = new Model_User(array(
                            'email' => $request->getPost('email'),
                            'type' => 'shop'
                        ));
                        $user->salt = md5(uniqid('', true));
                        $user->password = md5($request->getPost('password1') . '_epelia_' . $user->salt);
                        try{
                            $user->save();
                        } catch(Exception $e){
                            $this->_helper->_redirector('failure');
                        }
                        $shop = new Model_Shop(array(
                            'user_id' => $user->id,
                            'name' => $request->getPost('shopname'),
                            'url' => $request->getPost('website'),
                            'company' => $request->getPost('company'),
                            'representative' => $request->getPost('firstname') . ' ' . $request->getPost('name'),
                            'phone' => $request->getPost('phone'),
                            'country' => $request->getPost('country')
                        ));
                        try{
                            $shop->save();
                        } catch(Exception $e){
                            echo $e->getMessage();
                            exit();
                            $this->_helper->_redirector('failure');
                        }
                        try{
                            $registerMail = Model_Email::find('register');
                            $mail = new Zend_Mail('UTF-8');
                            $content = str_replace('#registerLink#', 'http://' . $_SERVER['HTTP_HOST'] . '/login/confirm/id/' . $user->id . '/code/' . md5($user->email . '_epelia_' . $user->salt) . '/', $registerMail->content);
                            $mail->setBodyText(strip_tags($content));
                            $mail->setFrom('mail@epelia.com', 'Epelia');
                            $mail->addTo($user->email);
                            $mail->setSubject($registerMail->subject);
                            $mail->send();
                        } catch(Exception $e){
                            $this->_helper->_redirector('failure');
                        }


                        $mail = new Zend_Mail('UTF-8');
                        $mail->setFrom('mail@epelia.com', 'Epelia');
                        $mail->addTo('hoesel@derhoesel.de', 'Epelia');
                        $mail->setSubject('Verkäufer Registrierung');
                        $mail->setBodyText(
                            "Neue Verkäufer-Anmeldung:\n\n" . 
                            "Firma: " . $request->getPost('company') . "\n" .
                            "Vorname: " . $request->getPost('firstname') . "\n" .
                            "Nachname: " . $request->getPost('name') . "\n" . 
                            "Telefon: " . $request->getPost('phone') . "\n" . 
                            "E-Mail: " . $request->getPost('email') . "\n" . 
                            "Land: " . $request->getPost('country') . "\n" . 
                            "Webseite: " . $request->getPost('website')
                        );
                        $mail->send();
                        $this->_helper->_redirector('success');
                    }
                }
            }
        }
        $this->view->form = $form;
    }

    public function successAction(){
        $this->view->headTitle('Vielen Dank für Ihre Registrierung | Epelia');
    }

    public function failureAction(){
        $this->view->headTitle('Es ist ein Fehler aufgetreten | Epelia');
    }

}