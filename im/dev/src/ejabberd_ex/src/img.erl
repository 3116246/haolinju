-module(img).

-export([image_type/1]).  
  
image_type(File) when is_list(File) ->  
    case file:read_file(File) of  
    {ok, Data} ->  
        image_type(Data);  
    _ ->  
        {error, openfile}  
    end;  
  
%% Gif header, width and height  
%% http://www.etsimo.uniovi.es/gifanim/gif87a.txt  
image_type(<<$G, $I, $F, $8, $9, $a, Width:16/little, Height:16/little, _/binary>>) ->  
    {gif, Width, Height};  
image_type(<<$G, $I, $F, $8, $7, $a, Width:16/little, Height:16/little, _/binary>>) ->  
    {gif, Width, Height};  
  
%% Png header  
%% ref: http://www.w3.org/TR/PNG/#5DataRep  
image_type(<<137, 80, 78, 71, 13, 10, 26, 10, _:4/signed-integer-unit:8, 73, 72, 68, 82, Width:32/signed-big, Height:32/signed-big, _/binary>>) ->  
    {png, Width, Height};  
  
%% Jpeg header  
%% ref:http://en.wikipedia.org/wiki/Jpeg#JPEG_files  
%%     http://www.obrador.com/essentialjpeg/headerinfo.htm  
  
image_type(<<16#FF, 16#D8, JpegData/binary>>) ->  
    {W, H} = parse_jpeg(JpegData),  
    {jpeg, W, H};  
  
image_type(_) ->  
    unknown.  
    
parse_jpeg(Jpeg) ->  
    parse_jpeg(Jpeg, {}).  
  
parse_jpeg(<<>>, Results) -> Results;  
parse_jpeg(<<16#FF, 16#C0, _:16,_:8, Height:16/signed-big, Width:16/signed-big, _/binary>>, _) ->
    parse_jpeg(<<>>, {Width, Height});  
parse_jpeg(<<_:8, Rest/binary>>, Results) ->  
    parse_jpeg(Rest, Results).     