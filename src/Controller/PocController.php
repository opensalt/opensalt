<?php
namespace App\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use BobV\LatexBundle\Generator\LatexGeneratorInterface;
use BobV\LatexBundle\Latex\Base\Standalone;
use BobV\LatexBundle\Latex\Element\CustomCommand;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Gregwar\Tex2png\Tex2png;
use Psr\Log\LoggerInterface;

class PocController extends Controller
{
    /**
     * @Route("/poc/index")
     */
    public function indexAction(Request $request)
    { 		
            chdir('/var/www/html/web/temp2');
          //$data = shell_exec('/home/opensalt/latexdockercmd.sh pdflatex');
            $output = shell_exec('latexdockercmd.sh /bin/sh -c "pdflatex 954b0c733921e9a3e9785097e68486314d8985cd.tex"');
            echo "<pre>$output</pre>";
            die;
    }
    /**
     * @Route("/poc/math")
     */	
	public function mathAction(){
              $content = '$x=\frac{1+y}{1+2z^2}$';
            $document = (new Standalone(md5($content)))->addElement(new CustomCommand($content));
            $latexGenerator = $this->get('bobv.latex.generator');
            // Generate pdf output
            $pdfLocation = $latexGenerator->generate($document);
	}
    /**
     * @Route("/poc/mathtx")
     */	
	public function mathtestAction(Request $request){     
	     $path=$request->server->get('DOCUMENT_ROOT').$request->getBasePath();
             Tex2png::create('\sum_{i = 0}^{i = n} \frac{i}{2}')->saveTo($path.'\sum2.png')->generate();
             die;
	}

}
