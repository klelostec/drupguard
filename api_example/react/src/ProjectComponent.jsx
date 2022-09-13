import React, { useState, useEffect }  from 'react';
import Carousel from 'react-bootstrap/Carousel';
import C3Chart from 'react-c3js';
import 'c3/c3.css';
import { useConfig } from "./Config";
import { Events, animateScroll as scroll} from "react-scroll";

const ProjectComponent = props => {
    const { getConfig } = useConfig();
    const [componentData, setComponentData] = useState({error:null, isLoaded:false, items:[]});

    // Note: the empty deps array [] means
    // this useEffect will run once
    // similar to componentDidMount()
    useEffect(() => {
        const loadData = () => {
            setComponentData({error:null, isLoaded:false, items:[]});
            fetch(getConfig("backend"), {
                headers: {
                    'Content-Type': 'application/json',
                    'X-AUTH-TOKEN': getConfig("token")
                }
            })
                .then(res => res.json())
                .then(
                    (result) => {
                        setComponentData({error:null, isLoaded:true, items:result});
                    },
                    // Note: it's important to handle errors here
                    // instead of a catch() block so that we don't swallow
                    // exceptions from actual bugs in components.
                    (error) => {
                        setComponentData({error:error, isLoaded:true, items:[]});
                        setTimeout(loadData, 5000);
                    }
                )
        }
        loadData();
        setInterval(() => {
            window.location.reload(false);
        }, getConfig("refresh"));
    }, [])

    useEffect(() => {
        if (componentData.items.length === 0) {
            Events.scrollEvent.remove('end');
        }
        else {
            const scrollToBottom = () => {
                Events.scrollEvent.remove('end');
                Events.scrollEvent.register('end', function() {
                    scrollToTop();
                });
                scroll.scrollToBottom({
                    containerId: 'scrollList',
                    smooth: 'linear',
                    duration: componentData.items.length*500
                });
            };
            const scrollToTop = () => {
                Events.scrollEvent.remove('end');
                Events.scrollEvent.register('end', function() {
                    scrollToBottom();
                });
                scroll.scrollToTop({
                    containerId: 'scrollList',
                    smooth: 'linear',
                    duration: componentData.items.length*500
                });
            };
            scrollToBottom();
        }
    }, [componentData])

    if (componentData.error) {
        return <div className="d-flex align-items-center justify-content-center vh-100 text-responsive">
            <h2 className="p-3">Error: {componentData.error.message}</h2>
        </div>;
    } else if (!componentData.isLoaded) {
        return <div className="d-flex align-items-center justify-content-center vh-100">
            <div className="spinner-border" role="status"></div>
        </div>;
    } else {
        const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'short',
            timeStyle: 'medium',
            timeZone: 'UTC'
        });
        const getProjectClass = (item) => {
            let state = 'other';
            if (item.lastAnalyse) {
                switch (parseInt(item.lastAnalyse.state)) {
                    case 1:
                        return 'danger';
                    case 2:
                        return 'warning';
                    case 3:
                        return 'success';
                    default:
                        return 'other';
                }
            }
            return state
        }
        const renderAnalyseMessage = (item) => {
            if (item.lastAnalyse && item.lastAnalyse.message) {
                return <div className="content-details d-flex align-items-center justify-content-center">
                    <p className={"p-3 project-" + getProjectClass(item)}>{item.lastAnalyse.message}</p>
                </div>;
            }
        }
        const getDonutData = (item) => {
            let states = {
                success: 0,
                warning: 0,
                danger: 0,
                other: 0
            }
            let data = [];
            if (item.lastAnalyse && item.lastAnalyse.analyseItems) {
                item.lastAnalyse.analyseItems.map( (analyseItem) => {
                    let state;
                    switch (parseInt(analyseItem.state)) {
                        case 1:
                            state = 'danger'
                            break;
                        case 2:
                        case 3:
                        case 4:
                            state = 'warning'
                            break;
                        case 5:
                            state = 'success'
                            break;
                        default:
                            state = 'other';
                            break;
                    }
                    states[state]++;
                    return undefined;
                });
                ['success', 'warning', 'danger', 'other'].map(state => {
                    if (states[state] > 0) {
                        data.push([state, states[state]]);
                    }
                    return undefined;
                });
            }
            return {
                data: {
                    columns: data,
                    type: "donut",
                    colors: {success: '#d1e7dd', warning: '#fff3cd', danger: '#f8d7da', other: '#e2e3e5'}
                }
            };
        }
        const renderDonut = (item) => {
            if (item.lastAnalyse && item.lastAnalyse.analyseItems && item.lastAnalyse.analyseItems.length) {
                return <div><C3Chart {...getDonutData(item)} /></div>;
            }
        }
        return (
            <div>
                <div className="row p-0 m-0">
                    <div className="col-2 p-0 m-0 vh-100 scrollable" id="scrollList">
                        {componentData.items.map(item => (
                            <div className="d-block p-1">
                                <div className="title-container d-flex">
                                    <div className={"project-bullet-mini my-1 me-3 project-" + getProjectClass(item)}></div>
                                    <h2 className="text-responsive-small">{item.name}</h2>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="col-10 p-0 m-0">
                        <Carousel interval={getConfig("interval")} controls={false} pause={false}>
                            {componentData.items.map(item => (
                                <Carousel.Item>
                                    <div className="d-flex align-items-center justify-content-center vh-100">
                                        <div className="text-responsive">
                                            <div className="title-container d-flex align-items-center justify-content-center">
                                                <div className={"project-bullet my-1 me-3 project-" + getProjectClass(item)}></div>
                                                <h2>{item.name}</h2>
                                            </div>
                                            <div className="content-container">
                                                <div className="content-details d-flex align-items-center justify-content-center">
                                                    <p>{dateFormatter.format(item.lastAnalyse.date * 1000)}</p>
                                                </div>
                                                {renderAnalyseMessage(item)}
                                                <div className="content-graph d-flex align-items-center justify-content-center">
                                                    {renderDonut(item)}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Carousel.Item>
                            ))}
                        </Carousel>
                    </div>
                </div>
            </div>
        );
    }
};
export default ProjectComponent;