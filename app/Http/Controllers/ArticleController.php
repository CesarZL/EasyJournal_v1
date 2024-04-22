<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\Template;
use App\Models\Coauthor;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Gemini\Laravel\Facades\Gemini;

class ArticleController extends Controller
{

    // Middleware para proteger las rutas del controlador
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Función para mostrar el dashboard
    public function index()
    {
        //retorna la vista del dashboard con los articulos del usuario logueado
        $articles = Article::where('user_id', auth()->user()->id)->get();

        $title = 'Borrar artículo';
        $text = "¿Estás seguro de que quieres borrar este artículo?";
        confirmDelete($title, $text);

        //retorna la vista del dashboard con los articulos del usuario logueado
        return view('dashboard', compact('articles'));
    }

    // Función para crear un nuevo artículo desde el dashboard
    public function store(Request $request)
    {
        //validaciones de los campos del formulario
        $request->validate([
            'title' => 'required',
        ]);

        //crea un nuevo articulo y lo guarda en la base de datos
        $article = new Article;
        $article->title = $request->title;
        $article->user_id = auth()->user()->id;
        $article->save();

        // Redirige al dashboard después de crear un nuevo artículo
        return redirect()->route('dashboard')->with('success', 'Artículo creado correctamente.');

        // return redirect()->route('templates')->with('success', 'Plantilla subida correctamente.');

    }

    // Función para mostrar la vista de edición de un artículo
    public function edit(Article $article)
    {
        // Pasa el contenido del campo 'content' a la vista
        $content = $article->content;

        //pasa los datos de las templates a la vista, esto para ver en el select las plantillas que tiene el usuario
        $templates = Template::where('user_id', auth()->user()->id)->get();
        
        // Retorna la vista 'edit-article' con los datos del artículo 
        return view('edit-article', compact('article', 'content', 'templates'));
        
    }
    
    // Función para actualizar un artículo y renderizar el pdf con el contenido actualizado
    public function update(Request $request, Article $article)
    {
        // Validar los datos del formulario
        $request->validate([
            'abstract' => 'required',
            'keywords' => 'required',
            'content' => 'required',
        ]);

        // Actualiza los campos del artículo
        $article->abstract = $request->abstract;
        $article->keywords = $request->keywords;
        $article->content = $request->content;
        $article->save();

        // Parsear el contenido del artículo
        $parsed_content = json_decode($article->content, true);
                
        // darle formato de \section al header, \subsection al header de otro nivel y \subsubsection al header de otro nivel y cada parrafo se le da formato será el contenido de su respectivo header
        $my_tex_content = '';
        foreach ($parsed_content['blocks'] as $block) {
            if ($block['type'] == 'header') {
                if ($block['data']['level'] == 1) {
                    $my_tex_content .= "\\section{" . $block['data']['text'] . "}\n";
                } elseif ($block['data']['level'] == 2) {
                    $my_tex_content .= "\\subsection{" . $block['data']['text'] . "}\n";
                } elseif ($block['data']['level'] == 3) {
                    $my_tex_content .= "\\subsubsection{" . $block['data']['text'] . "}\n";
                }
            } elseif ($block['type'] == 'paragraph') {
                $my_tex_content .= $block['data']['text'] . "\n";
            }
        }

        // si no se selecciona una plantilla se crea una plantilla por defecto
        if($request->template == null){
                $tex_content = "\\documentclass{article}\n";
                $tex_content .= "\\usepackage[margin=2cm]{geometry}\n";
                $tex_content .= "\\usepackage{orcidlink}\n";
                $tex_content .= "\\usepackage{authblk}\n";
                $tex_content .= "\\usepackage[utf8]{inputenc}\n";
                $tex_content .= "\\usepackage{longtable}\n";
                $tex_content .= "\\usepackage{graphicx}\n";
                $tex_content .= "\\usepackage{subfig}\n";
                $tex_content .= "\\date{}\n";
                $tex_content .= "\\setcounter{Maxaffil}{0}\n";
                $tex_content .= "\\renewcommand\\Affilfont{\\itshape\\small}\n";
                $tex_content .= "\\providecommand{\\keywords}[1]\n";
                $tex_content .= "{\n";
                $tex_content .= "  \\small  \n";
                $tex_content .= "  \\textbf{\\textit{Keywords---}} #1\n";
                $tex_content .= "} \n";
                $tex_content .= "\\title{" . $article->title . "}\n";

                for ($i = 0; $i < count($article->coauthors) + 1; $i++) {
                    if ($i == 0) {
                        $tex_content .= "\\author[" . ($i + 1) . ",*]{" . auth()->user()->name . " " . auth()->user()->father_surname . " " . auth()->user()->mother_surname . " \\orcidlink{" . auth()->user()->orcid . "}} \n";
                    } else {
                        $tex_content .= "\\author[" . ($i + 1) . "]{" . $article->coauthors[$i - 1]->name . " " . $article->coauthors[$i - 1]->father_surname . " " . $article->coauthors[$i - 1]->mother_surname . " \\orcidlink{" . $article->coauthors[$i - 1]->orcid . "}}\n";
                    }
                }

                for ($i = 0; $i < count($article->coauthors) + 1; $i++) {
                    if ($i == 0) {
                        $tex_content .= "\\affil[" . ($i + 1) . "]{" . auth()->user()->affiliation . ", " . auth()->user()->institution . "}\n";
                    } else {
                        $tex_content .= "\\affil[" . ($i + 1) . "]{" . $article->coauthors[$i - 1]->affiliation . ", " . $article->coauthors[$i - 1]->institution . "}\n";
                    }
                }

                $tex_content .= "\\begin{document}\n";
                $tex_content .= "\\maketitle\n";
                $tex_content .= "\\begin{abstract}\n";
                $tex_content .= $article->abstract . "\n";
                $tex_content .= "\\end{abstract}\n";
                $tex_content .= "\\keywords{" . $article->keywords . "}\n";
                $tex_content .= $my_tex_content . "\n";
                $tex_content .= "\\end{document}\n";

                // crea la carpeta templates_public si no existe
                if (!File::exists(public_path('templates_public/' . $article->id))) {
                    File::makeDirectory(public_path('templates_public/' . $article->id), 0777, true);
                }else{
                    // borrar todo dentro de la carpeta
                    File::cleanDirectory(public_path('templates_public/' . $article->id));
                }

                // crea el archivo tex
                File::put(public_path('templates_public/' . $article->id . '/' . $article->id . '.tex'), $tex_content);

                // ejecutar el comando pdflatex para compilar el archivo .tex
                $process = new Process(['C:\Users\cesar\AppData\Local\Programs\MiKTeX\miktex\bin\x64\pdflatex.exe', "-output-directory=templates_public/{$article->id}", public_path('templates_public/' . $article->id . '/' . $article->id . '.tex')]);
                $process->run();

                // verificar si hubo un error al compilar el archivo .tex
                if (!$process->isSuccessful()) {
                    // Esto se tiene que cambiar por un mensaje de error en la vista
                    throw new ProcessFailedException($process);
                }

                // obtener la url del pdf generado
                $pdf_url = asset("templates_public/{$article->id}/{$article->id}.pdf");

                //mandar el url del pdf generado a la ruta de la vista
                return redirect()->route('articles.edit', $article->id)->with('pdf_url', $pdf_url);
        }
        else{

            // busca la plantilla seleccionada 
            $template = Template::find($request->template);
            $template_path = $template->file;

            // crea la carpeta templates_public si no existe
            if (!File::exists(public_path('templates_public/' . $article->id))) {
                File::makeDirectory(public_path('templates_public/' . $article->id), 0777, true);
            }else{
                // borrar todo dentro de la carpeta
                File::cleanDirectory(public_path('templates_public/' . $article->id));
            }
            
            // extraer el contenido de la plantilla seleccionada
            $zip = new ZipArchive;
            if ($zip->open(storage_path('app/' . $template_path)) === TRUE) {
                $zip->extractTo(public_path('templates_public/' . $article->id));
                $zip->close();
            } else {
                dd('No se pudo abrir el archivo ZIP');
            }

            // buscar el archivo .tex con mayor tamaño
            $files = glob(public_path('templates_public/' . $article->id . '/*.tex'));
            $largestFile = '';
            $largestSize = 0;

            foreach ($files as $file) {
                $size = filesize($file);
                if ($size > $largestSize) {
                    $largestSize = $size;
                    $largestFile = $file;
                }
            }

            // renombrar el archivo .tex con el id del artículo
            $new_file_name = public_path('templates_public/' . $article->id . '/' . $article->id . '.tex');
            rename($largestFile, $new_file_name);
            
            // función para eliminar comentarios y secciones innecesarias del archivo .tex
            function remove_tex_comments($input_file, $output_file, $sections)
            {
                $content = file($input_file, FILE_IGNORE_NEW_LINES);
                $fp = fopen($output_file, 'w');

                $remove = false;
                $sectionbase_written = false;
                $in_abstract = false;

                foreach ($content as $line) {
                    $line = explode("%", $line)[0]; // Eliminar comentarios
                    if (strpos($line, "\\begin{abstract}") !== false) {
                        $in_abstract = true;
                        $remove = true;
                        fwrite($fp, $line . "\n");
                        continue;
                    } elseif (strpos($line, "\\end{abstract}") !== false) {
                        $in_abstract = false;
                        $remove = false;
                        fwrite($fp, $line . "\n");
                        continue;
                    } elseif ($in_abstract) {
                        continue;
                    }
                    if (!$remove && preg_match('/' . implode('|', array_map('preg_quote', $sections)) . '/', $line)) {
                        if (!$sectionbase_written) {
                            fwrite($fp, "\n\\section{SECTIONBASE}\n\n\n");
                            $sectionbase_written = true;
                        }
                        $remove = true;
                    } elseif ($remove && trim($line) == "}") {
                        $remove = false;
                    } elseif (!$remove || preg_match('/' . implode('|', array_map('preg_quote', ["\\end{document}", "\\EOD"])) . '/', $line)) {
                        fwrite($fp, $line . "\n");
                    }
                }
                fclose($fp);
            }

            // variables para los archivos de entrada y salida
            $input_file = public_path('templates_public/' . $article->id . '/' . $article->id . '.tex');
            // $output_file = public_path('templates_public/' . $article->id . '/' . $article->id . '_modified.tex');
            $output_file = public_path('templates_public/' . $article->id . '/' . $article->id . '.tex');
            // secciones y comandos a remover del archivo .tex
            $sections_to_remove = ["\\section{", "\\section*{", "\\subsection{", "\\subsection*{", "\\subsubsection{", "\\subsubsection*{", "\\appendixtitles{", "\\appendixtitle{", "\\appendix{", "\\appendix*{", "\\setcounter{section", "\\noindent"];
            remove_tex_comments($input_file, $output_file, $sections_to_remove);

            // leer el contenido del archivo .tex modificado
            $tex_content = file_get_contents($output_file);

            // prompt para el modelo de lenguaje gemini pro si el articulo tiene coautores

            if (count($article->coauthors) > 0) {

                $prompt = "Your purpose is to fill in sections of latex files with the information I will provide you, first, you are going to fill the author information, coauthors and affiliations and all those information that is needed, if you don't have the information, you must leave it blank, if you have extra data from the author or coauthors but isnt requiere in the template you must leave it blank, if the authors or coauthors share the same affiliation and institution then they can share the same number of affiliation and institution, if they don't share the same affiliation and institution then they need to have different numbers of affiliation and institution. You are going to fill that information with this data: \n\nThis is the principal author information: \n\nName: " . (auth()->user()->name ? auth()->user()->name . " " : "") . (auth()->user()->father_surname ? auth()->user()->father_surname . " " : "") . (auth()->user()->mother_surname ? auth()->user()->mother_surname : "") . "\nORCID: " . (auth()->user()->orcid ? auth()->user()->orcid : "") . "\nAffiliation: " . (auth()->user()->affiliation ? auth()->user()->affiliation : "") . "\nInstitution: " . (auth()->user()->institution ? auth()->user()->institution : "") . "\nEmail: " . (auth()->user()->email ? auth()->user()->email : "") . "\n\nAnd this is the coauthors information: \n\n";
                for ($i = 0; $i < count($article->coauthors); $i++) {
                    $prompt .= "Name: " . $article->coauthors[$i]->name . " " . $article->coauthors[$i]->father_surname . " " . $article->coauthors[$i]->mother_surname . "\nORCID: " . $article->coauthors[$i]->orcid . "\nAffiliation: " . $article->coauthors[$i]->affiliation . "\nInstitution: " . $article->coauthors[$i]->institution . "\nEmail: " . $article->coauthors[$i]->email . "\n\n";
                }
                $prompt .= "\n\nAfter you fill the author information, you're going to give me the updated latex file ready to compile without any explanation, just the code. The latex without the author information is in the file " . $tex_content . ".\n\n";    

            } else {
                $prompt = "Your purpose is to fill in sections of latex files with the information I will provide you, first, you are going to fill the author information and all those information that is needed, if you don't have the information, you must leave it blank, if you have extra data from the author but isnt requiere in the template you must leave it blank, if there is just one author you must have only one affiliation and institution. In this case there is just one author and you must delete other dummy author or coauthor in the latex file and put only the author that i am given to you. You are going to fill that information with this data: \n\nThis is the principal author information: \n\nName: " . (auth()->user()->name ? auth()->user()->name . " " : "") . (auth()->user()->father_surname ? auth()->user()->father_surname . " " : "") . (auth()->user()->mother_surname ? auth()->user()->mother_surname : "") . "\nORCID: " . (auth()->user()->orcid ? auth()->user()->orcid : "") . "\nAffiliation: " . (auth()->user()->affiliation ? auth()->user()->affiliation : "") . "\nInstitution: " . (auth()->user()->institution ? auth()->user()->institution : "") . "\nEmail: " . (auth()->user()->email ? auth()->user()->email : "");
                $prompt .= "\n\nAfter you fill the author information, you're going to give me the updated latex file ready to compile without any explanation, just the code. The latex without the author information is in the file " . $tex_content . ".\n\n";    
            }

            // $prompt = "Your purpose is to fill in sections of latex files with the information I will provide you, first, you are going to fill the author information, coauthors and affiliations and all those information that is needed, if you don't have the information, you must leave it blank, if you have extra data from the author or coauthors but isnt requiere in the template you must leave it blank, if the authors or coauthors share the same affiliation and institution then they can share the same number of affiliation and institution, if they don't share the same affiliation and institution then they need to have different numbers of affiliation and institution. You are going to fill that information with this data: \n\nThis is the principal author information: \n\nName: " . (auth()->user()->name ? auth()->user()->name . " " : "") . (auth()->user()->father_surname ? auth()->user()->father_surname . " " : "") . (auth()->user()->mother_surname ? auth()->user()->mother_surname : "") . "\nORCID: " . (auth()->user()->orcid ? auth()->user()->orcid : "") . "\nAffiliation: " . (auth()->user()->affiliation ? auth()->user()->affiliation : "") . "\nInstitution: " . (auth()->user()->institution ? auth()->user()->institution : "") . "\nEmail: " . (auth()->user()->email ? auth()->user()->email : "") . "\n\nAnd this is the coauthors information: \n\n";
            // for ($i = 0; $i < count($article->coauthors); $i++) {
            //     $prompt .= "Name: " . $article->coauthors[$i]->name . " " . $article->coauthors[$i]->father_surname . " " . $article->coauthors[$i]->mother_surname . "\nORCID: " . $article->coauthors[$i]->orcid . "\nAffiliation: " . $article->coauthors[$i]->affiliation . "\nInstitution: " . $article->coauthors[$i]->institution . "\nEmail: " . $article->coauthors[$i]->email . "\n\n";
            // }
            // $prompt .= "\n\nAfter you fill the author information, you're going to give me the updated latex file ready to compile without any explanation, just the code. The latex without the author information is in the file " . $tex_content . ".\n\n";

            // mandar el contenido del archivo .tex modificado al modelo de lenguaje gemini pro
            $tex_content = Gemini::geminiPro()->generateContent($prompt);

            // convertir el contenido del archivo .tex modificado a texto plano
            $tex_content = $tex_content->text();

            // busca el \title{} que no esté comentado y lo reemplaza por el título del artículo
            $tex_content = preg_replace('/(\\\\title\{.*\})/', "\\title{" . $article->title . "}", $tex_content);

            // busca el \begin{abstract} y \end{abstract} que no esté comentado y lo reemplaza por el abstract del artículo
            $tex_content = preg_replace('/(\\\\begin\{abstract\}.*\\\\end\{abstract\})/s', "\\begin{abstract}\n" . $article->abstract . "\n\\end{abstract}", $tex_content);

            // busca el \keywords{} o \keywords[]{} o \keywords{}[] que no esté comentado y si no existe se busca el \begin{keywords} y \end{keywords} que no esté comentado y lo reemplaza por las keywords del artículo
            if (preg_match('/(\\\\keywords\{.*\})/', $tex_content)) {
                $tex_content = preg_replace('/(\\\\keywords\{.*\})/', "\\keywords{" . $article->keywords . "}", $tex_content);
            } elseif (preg_match('/(\\\\begin\{keywords\}.*\\\\end\{keywords\})/s', $tex_content)) {
                $tex_content = preg_replace('/(\\\\begin\{keywords\}.*\\\\end\{keywords\})/s', "\\begin{keywords}\n" . $article->keywords . "\n\\end{keywords}", $tex_content);
            }

            //buscar y reemplazar &nbsp; por espacio en blanco
            $my_tex_content = str_replace("&nbsp;", " ", $my_tex_content);
            //buscar y reemplazar &, _, %, $, #, {, }, ~, ^, \ por su respectiva secuencia de escape
            $my_tex_content = str_replace("&", "\\&", $my_tex_content);
            $my_tex_content = str_replace("_", "\\_", $my_tex_content);
            $my_tex_content = str_replace("%", "\\%", $my_tex_content);
            $my_tex_content = str_replace("$", "\\$", $my_tex_content);
            $my_tex_content = str_replace("#", "\\#", $my_tex_content);
            $my_tex_content = str_replace("~", "\\textasciitilde ", $my_tex_content);
            $my_tex_content = str_replace("^", "\\textasciicircum ", $my_tex_content);

            // busca la sección SECTIONBASE y la reemplaza por el contenido del artículo
            $tex_content = preg_replace('/(\\\\section\{SECTIONBASE\})/', $my_tex_content, $tex_content);
            
            // guardando el contenido del articulo en un archivo .tex
            file_put_contents($output_file, $tex_content);            

            // compilar el archivo .tex con -interaction=nonstopmode para que no se detenga en caso de error
            // $process = new Process(['C:\Users\cesar\AppData\Local\Programs\MiKTeX\miktex\bin\x64\pdflatex.exe', "-output-directory=templates_public/{$article->id}", $output_file]);
            $process = new Process(['C:\Users\cesar\AppData\Local\Programs\MiKTeX\miktex\bin\x64\pdflatex.exe', "-interaction=nonstopmode", "-output-directory=templates_public/{$article->id}", $output_file]);
            $process->run();

            // verificar si hubo un error al compilar el archivo .tex
            if (!$process->isSuccessful()) {
                // Esto se tiene que cambiar por un mensaje de error en la vista
                // throw new ProcessFailedException($process);

                // borrar todo dentro de la carpeta y regresar a la vista con un mensaje de error
                File::cleanDirectory(public_path('templates_public/' . $article->id));
                return redirect()->route('articles.edit', $article->id)->with('error', 'No se pudo generar el PDF de este artículo.');

            }

            // obtener la url del pdf generado
            $pdf_url = asset("templates_public/{$article->id}/{$article->id}.pdf");
            return redirect()->route('articles.edit', $article->id)->with('pdf_url', $pdf_url);
        }
    }

    // Función para descargar el pdf de un artículo
    public function pdf(Article $article)
    {
        // obtener la url del pdf generado
        $pdf_url = asset("templates_public/{$article->id}/{$article->id}.pdf");

        // descargar el pdf
        return response()->download(public_path("templates_public/{$article->id}/{$article->id}.pdf"));
    }

    // Función para descargar el zip de un artículo
    public function zip(Article $article)
    {

        // crear un nuevo archivo zip con todo el contenido de la carpeta del artículo
        $zip_file = public_path("templates_public/{$article->id}/{$article->id}.zip");
        $zip = new ZipArchive;
        $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path("templates_public/{$article->id}")), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $name => $file) {
            // saltar los directorios
            if (!$file->isDir()) {
                // obtener la ruta del archivo
                $filePath = $file->getRealPath();
                // obtener la ruta relativa del archivo
                $relativePath = substr($filePath, strlen(public_path("templates_public/{$article->id}")) + 1);
                // añadir el archivo al archivo zip
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // descargar el archivo zip
        return response()->download($zip_file);
    }

    // Función para mostrar la vista de edición de detalles de un artículo
    public function edit_details($id)
    {
        // buscar el articulo por id
        $article = Article::find($id);

        // encontrar todos los coautores que agregó el usuario
        $coauthors = Coauthor::where('created_by', auth()->user()->id)->get();

        return view('edit-details', ['article' => $article], ['coauthors' => $coauthors]);
    }


    // Función para actualizar los detalles del artículo y añadir/quitar coautores
    public function updateDetails(Request $request, Article $article)
    {
        // Validar los datos del formulario
        $request->validate([
            'title' => 'required',
            'coauthors' => 'array', // Asegura que coauthors sea un arreglo
        ]);

        // Actualiza el título del artículo
        $article->title = $request->title;
        $article->save();

        // Actualiza los coautores del artículo
        $article->coauthors()->sync($request->coauthors);


        // Redirige de vuelta a la página de detalles del artículo
        return redirect()->route('articles.edit', $article->id)->with('success', 'Detalles actualizados correctamente.');
        
    }
    
    // Función para eliminar un artículo de la base de datos
    public function destroy($id)
    {
        // Buscar el artículo por su ID
        $article = Article::find($id);
        // Eliminar el artículo de la base de datos
        $article->delete();

        // borrar la carpeta del artículo
        File::deleteDirectory(public_path('templates_public/' . $id));

        return redirect()->route('dashboard');
    }
}
