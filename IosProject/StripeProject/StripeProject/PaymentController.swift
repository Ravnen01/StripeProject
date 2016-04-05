//
//  PaymentController.swift
//  StripeProject
//
//  Created by Corentin LECOMTE on 29/03/2016.
//  Copyright © 2016 LECOR. All rights reserved.
//

import UIKit
import Stripe
import LocalAuthentication


class PaymentController: UIViewController, STPPaymentCardTextFieldDelegate, UIAlertViewDelegate {

    @IBOutlet var saveButon: UIButton!
    
    @IBOutlet var emailTextField: UITextField!
    @IBOutlet var cardNumberTextField: UITextField!
    @IBOutlet var expireDateTextField: UITextField!
    @IBOutlet var cvcTextField: UITextField!
    
    override func viewDidLoad() {
        super.viewDidLoad()
            }
   
    
    @IBAction func save(sender: AnyObject) {
        autenticateUser()
        
    }
    
    func handleError(error: NSError){
        UIAlertView(title: "Please Try Again", message: error.localizedDescription, delegate: nil, cancelButtonTitle: "OK").show()
    }
    
    func createBackendChargeWithToken(token: STPToken, completion: PKPaymentAuthorizationStatus ->()){
        let url=NSURL(string: "https://lucien.appsolute-preprod.fr/iem/lecomteCorentin.php")!
        //let url=NSURL(string: "http://stripeproject.azurewebsites.net/index.php")!
        let request=NSMutableURLRequest(URL: url)
        request.HTTPMethod="POST"
        let body="stripeToken="+token.tokenId
        request.HTTPBody=body.dataUsingEncoding(NSUTF8StringEncoding)
        let configuration=NSURLSessionConfiguration.ephemeralSessionConfiguration()
        let session=NSURLSession(configuration: configuration)
        let task=session.dataTaskWithRequest(request){ (data, reponse, error) -> Void in
            if error != nil{
                completion(PKPaymentAuthorizationStatus.Success)
            }else{
                completion(PKPaymentAuthorizationStatus.Failure)
            }
        }
        task.resume()
        
    }
    
    func autanticationSucces(){
        let stripeCard=STPCard()
        if expireDateTextField.text!.isEmpty==false{
            let expirationDate=expireDateTextField.text!.componentsSeparatedByString("/")
            let expMonth=UInt(expirationDate[0])
            let expYear=UInt(expirationDate[1])
            
            stripeCard.number=cardNumberTextField.text
            stripeCard.cvc=cvcTextField.text
            stripeCard.expMonth=expMonth!
            stripeCard.expYear=expYear!
        }
        
        
        STPAPIClient.sharedClient().createTokenWithCard(stripeCard) { (token, error) -> Void in
            if let error=error{
                self.handleError(error)
            }
            else if let token=token{
                self.createBackendChargeWithToken(token) { status in
                    if status==PKPaymentAuthorizationStatus.Success{
                        UIAlertView(title: "Paiement accepté", message: "Votre paiement a été accepté", delegate: nil, cancelButtonTitle: "OK").show()
                    }else{
                        UIAlertView(title: "Paiement accepté", message: "Votre payment a été accepté", delegate: nil, cancelButtonTitle: "OK").show()
                    }
                    
                }
            }
        }
    }
    
    func autenticateUser(){
        let context: LAContext = LAContext()
        var error: NSError?
        let reasonString = "Authentication is needed to access your payment"
        if context.canEvaluatePolicy(LAPolicy.DeviceOwnerAuthenticationWithBiometrics, error: &error){
            [context.evaluatePolicy(LAPolicy.DeviceOwnerAuthenticationWithBiometrics, localizedReason: reasonString, reply: {(success: Bool, evalPolicyError: NSError?)-> Void in
                if success{
                    self.autanticationSucces()
                }else{
                    print(evalPolicyError?.localizedDescription)
                    switch evalPolicyError!.code{
                    case LAError.SystemCancel.rawValue: print("Authentication was cancelled by the system")
                    case LAError.UserCancel.rawValue: print("Authentication was cancelled by the user")
                    case LAError.UserFallback.rawValue:
                        print("User selected to enter custom password")
                        NSOperationQueue.mainQueue().addOperationWithBlock({ () -> Void in
                            self.showPasswordAlert()
                        })
                        
                    default:
                        print("Authentication failed")
                        NSOperationQueue.mainQueue().addOperationWithBlock({ () -> Void in
                            self.showPasswordAlert()
                        })
                    }
                }
            })]
        }else{
            // If the security policy cannot be evaluated then show a short message depending on the error.
            switch error!.code{
                
            case LAError.TouchIDNotEnrolled.rawValue:
                print("TouchID is not enrolled")
                
            case LAError.PasscodeNotSet.rawValue:
                print("A passcode has not been set")
                
            default:
                // The LAError.TouchIDNotAvailable case.
                print("TouchID not available")
            }
            
            // Optionally the error description can be displayed on the console.
            print(error?.localizedDescription)
            
            // Show the custom alert view to allow users to enter the password.
            self.showPasswordAlert()
        }
    }
    
    func showPasswordAlert() {
        let passwordAlert : UIAlertView = UIAlertView(title: "TouchIDDemo", message: "Please type your password", delegate: self, cancelButtonTitle: "Cancel", otherButtonTitles: "Okay")
        passwordAlert.alertViewStyle = UIAlertViewStyle.SecureTextInput
        passwordAlert.show()
    }
    
    
    
}
