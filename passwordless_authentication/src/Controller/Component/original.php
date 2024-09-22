<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Log\Log;

class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Twilio');
    }

    public function index()
    {
        $query = $this->Users->find();
        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    public function view($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        $this->set(compact('user'));
    }

    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function edit($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }

    public function requestCode()
    {
        if ($this->request->is('post')) {
            $phoneNumber = $this->request->getData('phone_number');
            $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT); // Using random_int for better randomness

            Log::info("Generating verification code for phone number: $phoneNumber");

            try {
                $success = $this->Twilio->sendVerificationCode(
                    $phoneNumber,
                    $code
                );
                if ($success) {
                    $this->request->getSession()->write('VerificationData', [
                        'phone_number' => $phoneNumber,
                        'code' => $code,
                        'expires' => time() + 1800 // 10 minutes expiration
                    ]);
                    Log::info("Verification code sent successfully to $phoneNumber");
                    $this->Flash->success('Verification code sent to your phone.');
                    return $this->redirect(['action' => 'verifyCode']);
                } else {
                    Log::error("Failed to send verification code to $phoneNumber");
                    $this->Flash->error('Failed to send verification code. Please try again.');
                }
            } catch (\Exception $e) {
                Log::error("Error sending verification code: " . $e->getMessage());
                $this->Flash->error('An error occurred while sending the verification code.');
            }
        }
        $this->set('user', $this->Users->newEmptyEntity());
    }

    public function verifyCode()
    {
        if ($this->request->is('post')) {
            $submittedCode = $this->request->getData('verification_code');
            $verificationData = $this->request->getSession()->read('VerificationData');

            Log::debug("Submitted code: $submittedCode");
            Log::debug("Verification data: " . json_encode($verificationData));

            if (!$verificationData) {
                Log::warning("No verification data found in session");
                $this->Flash->error('No verification data found. Please request a new code.');
                return $this->redirect(['action' => 'requestCode']);
            }

            if (time() >= $verificationData['expires']) {
                Log::info("Verification code expired for phone number: {$verificationData['phone_number']}");
                $this->Flash->error('Verification code has expired. Please request a new code.');
                $this->request->getSession()->delete('VerificationData');
                return $this->redirect(['action' => 'requestCode']);
            }

            if ($submittedCode !== $verificationData['code']) {
                Log::warning("Invalid verification code submitted for phone number: {$verificationData['phone_number']}");
                $this->Flash->error('Invalid verification code. Please try again.');
                return;
            }

            // Code is valid, create the user
            $user = $this->Users->newEntity([
                'phone_number' => $verificationData['phone_number'],
                'is_verified' => true
            ]);

            if ($this->Users->save($user)) {
                Log::info("User account created and verified for phone number: {$verificationData['phone_number']}");
                $this->Flash->success('Your phone number has been verified and your account has been created.');
                $this->request->getSession()->delete('VerificationData');
                return $this->redirect(['action' => 'login']);
            } else {
                Log::error("Failed to create user account for phone number: {$verificationData['phone_number']}");
                $this->Flash->error('There was a problem creating your account.');
            }
        }
        $this->set('user', $this->Users->newEmptyEntity());
    }
}
