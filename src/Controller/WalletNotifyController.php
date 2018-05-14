<?php
namespace App\Controller;

use Denpa\Bitcoin\Client as BitcoinClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletNotifyController Extends Controller 
{
    /**
     * @Route("/wallet/notify/{txid}", name="user_notify")
     */
    public function index($txid, \Swift_Mailer $mailer){

        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        if (!$txid) {
            throw $this->createNotFoundException(
                'No TXID was passed: '.$txid
            );
        }
        $aTransaction = $bitcoind->GetTransaction($txid)->get();

        $sAmount = $aTransaction['amount'];
        $sConfirmations = $aTransaction['confirmations'];
        $sTXID = $aTransaction['txid'];
        $sAccount = $aTransaction['details'][0]['account'];
        $sAddress = $aTransaction['details'][0]['address'];
        $sCategory = $aTransaction['details'][0]['category'];

        $message = (new \Swift_Message($sCategory.' transaction'))
            ->setFrom('hello@peterdalby.me')
            ->setTo($sAccount)
            ->setBody(
                $this->renderView(
                    'emails/transactionNotify.html.twig',
                    [
                        'txid' => $sTXID,
                        'account' => $sAccount,
                        'amount' => $sAmount,
                        'confirmations' => $sConfirmations,
                        'address' => $sAddress,
                        'category' => $sCategory,
                    ]
                ),
                'text/html'
            )
        ;
    $mailer->send($message);
    return $this->redirectToRoute('user_wallet');
    }

}


?>