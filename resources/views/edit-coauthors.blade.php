<x-app-layout>

    <div class="flex flex-col xl:flex-row ">

        <!-- Columna izquierda -->
        <div class="flex-1 xl:pe-3 py-3 xl:py-0">
            <div class="p-5 rounded-lg bg-gray-50 dark:bg-gray-800 overflow-y-auto h-screen"> 

                {{-- card header 1 --}}
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-4 dark:text-white">Editar coautor</h2>
                </div>

                {{-- Horizontal line  --}}
                <div class="border-b border-gray-300 dark:border-gray-600 my-4"></div>
                
                <form action="{{ route('coauthors.update', $coauthor) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mt-4">
                            <x-label for="name" :value="__('Nombre')" />
                            <x-input id="name" name="name" type="text" value="{{ old('name', $coauthor->name) }}" />
                            @error('name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="surname" :value="__('Apellido paterno')" />
                            <x-input id="surname" name="surname" type="text" value="{{ old('surname', $coauthor->surname) }}" />
                            @error('surname')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="last_name" :value="__('Apellido materno')" />
                            <x-input id="last_name" name="last_name" type="text" value="{{ old('last_name', $coauthor->last_name) }}" />
                            @error('last_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="email" :value="__('Correo electrónico')" />
                            <x-input id="email" name="email" type="email" value="{{ old('email', $coauthor->email) }}" />
                            @error('email')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="institution" :value="__('Institución')" />
                            <x-input id="institution" name="institution" type="text" value="{{ old('institution', $coauthor->institution) }}" />
                            @error('institution')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="country" :value="__('País')" />
                            <x-input id="country" name="country" type="text" value="{{ old('country', $coauthor->country) }}" />
                            @error('country')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="phone" :value="__('Teléfono')" />
                            <x-input id="phone" name="phone" type="text" value="{{ old('phone', $coauthor->phone) }}" />
                            @error('phone')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="address" :value="__('Dirección')" />
                            <x-input id="address" name="address" type="text" value="{{ old('address', $coauthor->address) }}" />
                            @error('address')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="orcid" :value="__('ORCID')" />
                            <x-input id="orcid" name="orcid" type="text" value="{{ old('orcid', $coauthor->orcid) }}" />
                            @error('orcid')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="scopus_id" :value="__('Scopus ID')" />
                            <x-input id="scopus_id" name="scopus_id" type="text" value="{{ old('scopus_id', $coauthor->scopus_id) }}" />
                            @error('scopus_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="researcher_id" :value="__('Researcher ID')" />
                            <x-input id="researcher_id" name="researcher_id" type="text" value="{{ old('researcher_id', $coauthor->researcher_id) }}" />
                            @error('researcher_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="url" :value="__('URL')" />
                            <x-input id="url" name="url" type="text" value="{{ old('url', $coauthor->url) }}" />
                            @error('url')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="affiliation" :value="__('Afiliación')" />
                            <x-input id="affiliation" name="affiliation" type="text" value="{{ old('affiliation', $coauthor->affiliation) }}" />
                            @error('affiliation')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <x-label for="affiliation_url" :value="__('URL de la afiliación')" />
                            <x-input id="affiliation_url" name="affiliation_url" type="text" value="{{ old('affiliation_url', $coauthor->affiliation_url) }}" />
                            @error('affiliation_url')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-2 mt-4">
                            <x-label for="biography" :value="__('Biografía')" />
                            <textarea rows="5" name="biography" id="biography" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">{{ old('biography', $coauthor->biography) }}</textarea>
                            @error('biography')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-4 flex justify-between space-x-4">
                        <div class="w-1/2">
                            <x-secondary-button class="block w-full">
                                <a href="{{ route('coauthors') }}">{{ __('Cancelar') }}</a>
                            </x-secondary-button>
                        </div>
                        
                        <div class="w-1/2">
                            <x-button class="block w-full">
                                {{ __('Guardar') }}
                            </x-button>
                        </div>
                    </div>

                </form>



            </div>
        </div>
        
                <!-- Columna derecha -->
                <div class="flex-1">
                    <div class="p-5 rounded-lg bg-gray-50 dark:bg-gray-800 h-screen">
        
                        {{-- card header 2 --}}
                        <div class="text-center">
                            <h2 class="text-2xl font-bold mb-4 dark:text-white">Coautores</h2>
                        </div>
        
                        {{-- Horizontal line  --}}
                        <div class="border-b border-gray-300 dark:border-gray-600 my-4"></div>
        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($coauthors as $coauthor)
                                
                           
                             <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-4 dark:text-white hover:cursor-pointer hover:bg-gray-600">
                                 <h3 class="text-lg font-bold mb-2 ">{{$coauthor->name}} {{$coauthor->surname}} {{$coauthor->last_name}}</h3>
                                 <div class="text-sm break-words">
                                    <p>{{$coauthor->institution}}, {{$coauthor->country}}</p>
                                    <p>{{$coauthor->email}}</p>
                                    <p>{{$coauthor->phone}}</p>
                                    <p>{{$coauthor->address}}</p>
                                    <p>{{$coauthor->ORCID}}</p>
                                </div>
        
        
                                 
                                 <div class="flex justify-end mt-4">
                                     <form id="delete-coauthor-{{$coauthor->id}}" action="{{ route('coauthors.destroy', $coauthor) }}" method="POST">
                                         @csrf
                                         @method('DELETE')
                                     </form>
                                     <button class="px-4 py-2 bg-red-500 text-white rounded-lg mr-2 hover:bg-red-600" onclick="event.preventDefault(); document.getElementById('delete-coauthor-{{$coauthor->id}}').submit()">Eliminar</button>
                                     <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600" onclick="window.location.href='{{ route('coauthors.edit', $coauthor->id) }}'">Editar</button>
                                 </div>
                             </div>
                             @endforeach
                         </div>
        
        
                    </div>
                </div>
      
    
    </div>


</x-app-layout>



