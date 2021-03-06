<?php
namespace App\Controller;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\UserType;
use Denpa\Bitcoin\Client as BitcoinClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserController extends Controller
{

    public function index()
    {
        //Quick workaround not being able to link controllers in the vendor folder to routes.yml
        return $this->redirectToRoute('fos_user_registration_register');
    }
    /**
     * @Route("/wallet/feedback/{message}/{rating}", name="user_feedback")
     */
    public function sendFeedback($message, $rating, \Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message('User Feedback'))
        ->setFrom('hello@peterdalby.me')
        ->setTo('hello@peterdalby.me')
        ->setBody(
            $this->renderView(
                'emails/feedback.html.twig',
                [
                    'message' => $message,
                    'rating' => $rating,
                ]
            ),
            'text/html'
        );
        $mailer->send($message); 
        //Also add to a table of feedback?
        return new Response (
            'success'
        );
    }
    public function getBitcoinData()
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        $aNetworkInfo = $bitcoind->getnetworkinfo();
        $aBlockchainInfo = $bitcoind->getblockchaininfo();
        $aTXCount = $bitcoind->getchaintxstats();
        $aMemPoolInfo = $bitcoind->getMemPoolInfo();
        $aTransactions = $bitcoind->ListTransactions('*', 50000)->get();
        $aWalletInfo = $bitcoind->getwalletinfo()->get();

        //Grab Bitcoin price in USD from coinmarketcap, to show the user a balance not in Bitcoin.
        $sCMCUrl = "https://api.coinmarketcap.com/v2/ticker/1/";
        $aData = json_decode(file_get_contents($sCMCUrl), true);
        $dRate = $aData['data']['quotes']['USD']['price'];
           
        return $this->render(
            'footer.html.twig',
                [
                    'info' => $aNetworkInfo,
                    'blockchaininfo' => $aBlockchainInfo,
                    'transactions' => $aTransactions,
                    'walletinfo' => $aWalletInfo,
                    'rate' => $dRate,
                    'txcount' => $aTXCount['txcount'],
                ]
        );
    }


    /**
     * @Route("/wallet", name="user_wallet")
     */
    public function userWallet()
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');
        $sAccount = $this->container->get('security.token_storage')->getToken()->getUser()->getEmail();

        //GetAddressesByLabel if(v => v0.17)
        $aAddresses = $bitcoind->GetAddressesByAccount($sAccount)->get();
        //Replace this with storing addresses in the user table

        $dConfirmedBalance = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $sAccount])->getBalance();

        //Received transactions (address associated with account)
        $aTransactions = $bitcoind->ListTransactions($sAccount)->get();

        //All transactions, filter sent transactions by comment)
        $listTransactions = $bitcoind->ListTransactions('*')->get();

        //The username from where the transaction was sent is included as a comment, so we can add sent transactions to the users transactions.
        for($i = 0; $i < count($listTransactions); $i++){
            if(array_key_exists('comment', $listTransactions[$i]) && $listTransactions[$i]['comment'] == $sAccount && $listTransactions[$i]['category'] == "send"){ //Sent transaction
                    array_push($aTransactions, $listTransactions[$i]);
            }
        }
        
        $sCMCUrl = "https://api.coinmarketcap.com/v2/ticker/1/";
        $aData = json_decode(file_get_contents($sCMCUrl), true);

        $dRate = $aData['data']['quotes']['USD']['price'];
        $dUSDBalance = $dRate * $dConfirmedBalance;

        return $this->render(
            'walletbase.html.twig', 
                [
                    'addresses' => $aAddresses,
                    'balance' => $dConfirmedBalance,
                    'transactions' => $aTransactions,
                    'usdbalance' => $dUSDBalance,
                    'rate' => $dRate,
                ]);
    }

    /**
     * @Route("/wallet/create/{format}", name="user_wallet_new_address")
     */
    public function newAddress($format)
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        $sAccount = $this->container->get('security.token_storage')->getToken()->getUser()->getEmail();

        switch($format){
            case 'bech32':
                $sAddress = $bitcoind->GetNewAddress($sAccount, "bech32");
                break;
            case 'p2sh':
                $sAddress = $bitcoind->GetNewAddress($sAccount, "p2sh-segwit");
                break;
            case 'legacy':
                $sAddress = $bitcoind->GetNewAddress($sAccount, "legacy");
                break;
            default:
                $sAddress = $bitcoind->GetNewAddress($sAccount, "p2sh-segwit");
        }

        return $this->redirectToRoute('user_wallet');
    }

     /**
     * @Route("/wallet/send/{toaddress}/{amount}/{token}", name="user_sendtransaction")
     */
    
    public function sendTransation($toaddress, $amount, $token, Request $request){

        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        $fromaccount = $this->container->get('security.token_storage')->getToken()->getUser()->getEmail();

        // 'send-bitcoin' is NOT the same value used in the template to generate the token
        if (!$this->isCsrfTokenValid('send-bitcoin', $token)) {
            return new Response(
                'CSRF token does not match.'
            );
        }

        $validation = $bitcoind->ValidateAddress($toaddress)->get();

        if ($validation['isvalid'] == 1){ 
            $oUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $fromaccount]);
            
            if (!$oUser) {
                throw $this->createNotFoundException(
                    'No user was found'
                );
            }
            //User found, lets get the balance.
            $accountBalance = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $fromaccount])->getBalance();

            if($amount <= 0)
            {
                return new Response(
                    'Amount has to be positive.'
                );
            }
            if ($accountBalance < $amount) {  
                return new Response(
                    'Amount is over balance.'
                );
            }
            if($validation['account'] == $fromaccount){
                return new Response(
                    'You cannot send to your own addresses.'
                );
            }
            if($validation['account'] !== $fromaccount && $accountBalance >= $amount && $amount > 0) {  
                $entityManager = $this->getDoctrine()->getManager();

                //Estimate fee for transaction to receive a confirmation in 10 blocks (very cheap)
                $fee = $bitcoind->EstimateSmartFee(10)->get();
                $dFee = number_format($fee['feerate'], 8); //btc/kb
                $bitcoind->SetTxFee($dFee);

                //Reference the accountname in the comment of the transaction, so it can be fetched later using ListTransactions
                $transactioncomment = $fromaccount;
                $bitcoind->walletpassphrase('password', 30);

                $transaction = $bitcoind->SendToAddress($toaddress, $amount, $transactioncomment, "", true); 
                $txid = $transaction->get();

                $finalBalance = $accountBalance - $amount;
                $oUser->setBalance($finalBalance);
                $entityManager->flush();
            
                return new Response(
                    $txid
                );
            }
        }
            
        else {
            //throw error, address is not a valid Bitcoin address.
            return new Response(
                'Address is not valid.'
            );
       }
    }
}
