<?php
namespace App\Controller;

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

class UserController extends Controller
{
    public function index()
    {
        //Quick workaround not being able to link controllers in the vendor folder to routes.yml
        return $this->redirectToRoute('fos_user_registration_register');
    }

    public function getBitcoinData()
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');

        $aNetworkInfo = $bitcoind->getnetworkinfo();
        $aBlockchainInfo = $bitcoind->getblockchaininfo();
        $aMemPoolInfo = $bitcoind->getMemPoolInfo();
        $aTransactions = $bitcoind->ListTransactions('*', 50)->get();
        $aWalletInfo = $bitcoind->getwalletinfo()->get();
           
        return $this->render(
            'footer.html.twig',
                [
                    'info' => $aNetworkInfo,
                    'blockchaininfo' => $aBlockchainInfo,
                    'transactions' => $aTransactions,
                    'walletinfo' => $aWalletInfo,
                ]
        );
    }

    /**
     * @Route("/wallet/send/{toaddress}/{amount}", name="user_sendtransaction")
     */
    public function sendTransation($toaddress, $amount){

    }

    /**
     * @Route("/wallet", name="user_wallet")
     */
    public function userWallet()
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');
        $sAccount = $this->container->get('security.token_storage')->getToken()->getUser()->getEmail();

        $aAddresses = $bitcoind->GetAddressesByAccount($sAccount)->get();
        //Total unconfirmed account balance
        $sUnconfirmedBalance = $bitcoind->GetBalance($sAccount, 0)->get();
        //Total confirmed account balance
        $sConfirmedBalance = $bitcoind->GetBalance($sAccount, 1)->get();
        $aTransactions = $bitcoind->ListTransactions($sAccount)->get();

        //Estimate fee for transaction being included within the next 6 blocks ~ 60 minutes
        $sFeeEstimate = $bitcoind->EstimateSmartFee(6)->get();

        return $this->render(
            'wallet/index.html.twig', 
                [
                    'addresses' => $aAddresses,
                    'unbalance' => $sUnconfirmedBalance,
                    'balance' => $sConfirmedBalance,
                    'transactions' => $aTransactions,
                    'estimate' => $sFeeEstimate,
                ]);
    }

    /**
     * @Route("/wallet/create/", name="user_wallet_new_address")
     */
    public function newAddress()
    {
        //Replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');

        $sAccount = $this->container->get('security.token_storage')->getToken()->getUser()->getEmail();

        $sAddress = $bitcoind->GetNewAddress($sAccount, "bech32");
        //$address = $bitcoind->GetNewAddress($username, "p2sh-segwit");
        //$address = $bitcoind->GetNewAddress($username, "legacy");
        
        return $this->redirectToRoute('user_wallet');
    }
}
