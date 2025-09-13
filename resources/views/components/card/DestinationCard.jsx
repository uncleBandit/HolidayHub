import React from 'react';
import { FaMapMarkerAlt, FaGlobeEurope, FaArrowRight } from 'react-icons/fa';

const DestinationCard = ({ destination }) => {
  if (!destination) return null;

  return (
    <div className="group relative w-full rounded-2xl overflow-hidden shadow-xl transform transition-transform duration-500 hover:shadow-2xl hover:scale-[1.03] cursor-pointer">
      {/* Background Image and Overlay */}
      <div className="relative w-full h-80">
        <img
          src={destination.cover_image}
          alt={destination.name}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
      </div>

      {/* Content */}
      <div className="absolute bottom-0 left-0 p-6 w-full text-white">
        <h3 className="font-bold text-3xl mb-1 drop-shadow-md">{destination.name}</h3>
        <p className="flex items-center text-sm font-light text-gray-200">
          <FaGlobeEurope className="mr-2" />
          {destination.country}
        </p>

        {/* View Details Button */}
        <div className="opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 mt-4">
          <button className="flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition-colors duration-300">
            Explore <FaArrowRight className="ml-2" />
          </button>
        </div>
      </div>
    </div>
  );
};

export default DestinationCard;
