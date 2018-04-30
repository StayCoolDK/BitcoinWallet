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

        //Uncomment and replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');

        $networkinfo = $bitcoind->getnetworkinfo();
        $blockchaininfo = $bitcoind->getblockchaininfo();
        $nodemempool = $bitcoind->getMemPoolInfo();
           
        return $this->render(
            'footer.html.twig',
                [
                    'info' => $networkinfo,
                    'blockchaininfo' => $blockchaininfo,
                ]
        );
    }

    /**
     * @Route("/wallet", name="user_wallet")
     */
    public function userWallet()
    {
        //Uncomment and replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getEmail();
        //or use the userID, and enable email being allowed to be changed (profile)


        //accounts can continue to be used in v0.17 by starting bitcoind with '-decprecatedrpc=accounts'
        //will be fully removed/deprecated in v0.18
        $addresses = $bitcoind->GetAddressesByAccount($username);

        //Total account balance
        $balance = $bitcoind->GetReceivedByAccount($username, 0);

        //List of transactions
        // $transactions = $bitcoind->ListTransactions($username, 10);

        return $this->render(
            'wallet/index.html.twig', 
                [
                    'addresses' => $addresses,
                    'balance' => $balance,
                  //  'transactions' => $transactions,
                ]);
    }

    /**
     * @Route("/wallet/create/", name="user_wallet_new_address")
     */
    public function newAddress()
    {
        //Uncomment and replace username:password to match your bitcoin config file
        $bitcoind = new BitcoinClient('http://StayCool:bandit1993@localhost:8332/');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getEmail();

        $address = $bitcoind->GetNewAddress($username, "bech32"); //bech32 addresses are native to the Segregated Witness softfork supporting cheaper transaction fees.

        return $this->redirectToRoute('user_wallet');
    }
}
